<?php

namespace App\Support;

/**
 * Speeds up the first paint of the pages as they are served: one stylesheet instead of several
 * and an early hero image request. (Fonts and their preloads are in resources/views/layouts/site.blade.php.)
 */
class PageSpeed
{
    private const BUNDLE_DIR = '/css/bundle';

    public function optimize(string $html): string
    {
        $parts = preg_split('#(</head>)#i', $html, 2, PREG_SPLIT_DELIM_CAPTURE);
        if (count($parts) !== 3) {
            return $html;
        }
        [$head, $close, $body] = $parts;

        $head = $this->bundleStyles($head);
        $head = $this->preloadHeroImage($head, $body);

        return $this->dropIndentation($this->minifyScripts($head.$close.$body));
    }

    /**
     * Local scripts are served minified from /js/min/ (App\Support\JsMinifier): same code, no comments
     * or indentation. The name holds the source's path and modification time, so an edit gets a new file.
     */
    private function minifyScripts(string $html): string
    {
        return preg_replace_callback(
            "#(<script\b[^>]*\bsrc=)(['\"])(/js/(?!min/)[^'\"?]+\.js)(?:\?[^'\"]*)?\\2#i",
            function ($m) {
                $file = public_path($m[3]);
                if (! is_file($file)) {
                    return $m[0];
                }
                // /js/pages/home.js -> /js/min/pages/home.<hash>.js
                $url = '/js/min/'.substr($m[3], 4, -3).'.'.substr(md5($m[3].'@'.filemtime($file)), 0, 12).'.js';
                $target = public_path($url);
                if (! is_file($target)) {
                    $js = (new JsMinifier)->minify((string) file_get_contents($file));
                    if (! is_dir(dirname($target)) && ! @mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
                        return $m[0];
                    }
                    $tmp = $target.'.'.getmypid().'.tmp';
                    if (@file_put_contents($tmp, $js) === false || ! @rename($tmp, $target)) {
                        return $m[0];
                    }
                }

                return $m[1].$m[2].$url.$m[2];
            },
            $html
        ) ?? $html;
    }

    /**
     * Line breaks plus indentation between tags become a single line break: the page renders the same
     * (a line break is white space, like the tabs were), with less markup. <pre>, <textarea>, <script>
     * and <style> are left exactly as they are.
     */
    private function dropIndentation(string $html): string
    {
        $parts = preg_split('#(<(pre|textarea|script|style)\b.*?</\2>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $html;
        }
        $out = '';
        for ($i = 0, $n = count($parts); $i < $n; $i++) {
            if ($i % 3 === 0) {
                $out .= preg_replace('/[ \t]*\r?\n\s*/', "\n", $parts[$i]) ?? $parts[$i];
            } elseif ($i % 3 === 1) {
                $out .= $parts[$i];
            }
            // $i % 3 === 2 is the captured tag name, already part of the block above
        }

        return $out;
    }

    /**
     * Early hints go right after the viewport meta, ahead of every stylesheet and script.
     */
    private function addEarly(string $head, string $tags): string
    {
        $result = preg_replace('#<meta[^>]*name=["\']viewport["\'][^>]*>#i', '$0'.$tags, $head, 1, $count);

        return $count ? $result : preg_replace('#<head\b[^>]*>#i', '$0'.$tags, $head, 1);
    }

    /**
     * Every local stylesheet and inline <style> in <head> goes into one file, in document order,
     * so the cascade is unchanged. The file name is a hash of the inputs, so browsers can keep
     * it for a year and an edited snapshot or stylesheet gets a new name.
     */
    private function bundleStyles(string $head): string
    {
        $pattern = "#<link\b(?=[^>]*\brel=['\"]stylesheet['\"])[^>]*>|<style\b[^>]*>.*?</style>#is";
        if (! preg_match_all($pattern, $head, $tags, PREG_OFFSET_CAPTURE)) {
            return $head;
        }

        $items = [];
        foreach ($tags[0] as [$tag, $offset]) {
            if (stripos($tag, '<style') === 0) {
                if (preg_match('#\bmedia=["\']([^"\']+)#i', $tag, $media) && strtolower($media[1]) !== 'all') {
                    continue;
                }
                $items[] = ['tag' => $tag, 'offset' => $offset, 'css' => preg_replace('#^<style\b[^>]*>|</style>$#i', '', $tag)];

                continue;
            }

            if (! preg_match("#\bhref=['\"]([^'\"]+)['\"]#i", $tag, $href)) {
                continue;
            }
            $path = parse_url(html_entity_decode($href[1]), PHP_URL_PATH);
            $file = $path ? public_path($path) : '';
            // Leave external and missing stylesheets where they are
            if (preg_match('#^(?:https?:)?//#i', $href[1]) || ! str_ends_with((string) $path, '.css') || ! is_file($file)) {
                continue;
            }
            preg_match("#\bmedia=['\"]([^'\"]+)['\"]#i", $tag, $media);
            $media = trim($media[1] ?? 'all');
            if (in_array(strtolower($media), ['print', 'none'], true)) {
                continue;
            }

            $items[] = ['tag' => $tag, 'offset' => $offset, 'file' => $file, 'path' => $path, 'media' => $media];
        }

        if (count($items) < 2) {
            return $head;
        }

        $key = '';
        foreach ($items as $item) {
            $key .= isset($item['file']) ? $item['path'].'@'.filemtime($item['file']).'|'.$item['media'] : md5($item['css']);
            $key .= "\n";
        }
        $url = self::BUNDLE_DIR.'/'.substr(md5($key), 0, 16).'.css';

        if (! is_file(public_path($url)) && ! $this->writeBundle(public_path($url), $items)) {
            return $head;
        }

        // Remove from the end so earlier offsets stay valid; the bundle takes the first tag's place
        foreach (array_reverse($items) as $i => $item) {
            $isFirst = $i === count($items) - 1;
            $replacement = $isFirst ? "<link rel='stylesheet' id='glowth-bundle-css' href='".$url."' media='all' />" : '';
            $head = substr_replace($head, $replacement, $item['offset'], strlen($item['tag']));
        }

        return $head;
    }

    private function writeBundle(string $target, array $items): bool
    {
        $css = '';
        foreach ($items as $item) {
            if (! isset($item['file'])) {
                $css .= $item['css']."\n";

                continue;
            }

            $part = $this->absoluteCssUrls((string) file_get_contents($item['file']), dirname($item['path']));
            // @charset is only valid at the very start of a file
            $part = preg_replace('#@charset\s+[^;]+;#i', '', $part) ?? $part;
            $css .= '/* '.$item['path']." */\n";
            $css .= strtolower($item['media']) === 'all' ? $part : '@media '.$item['media'].'{'.$part.'}';
            $css .= "\n";
        }

        if (! is_dir(dirname($target)) && ! @mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
            return false;
        }
        // Write then rename, so a visitor never gets a half-written file
        $tmp = $target.'.'.getmypid().'.tmp';

        return @file_put_contents($tmp, $css) !== false && @rename($tmp, $target);
    }

    /**
     * Relative url() values in a stylesheet point next to the original file; the bundle lives elsewhere.
     */
    private function absoluteCssUrls(string $css, string $dir): string
    {
        return preg_replace_callback(
            '#url\(\s*([\'"]?)(?!data:|https?:|//|/|\#)([^\'")]+)\1\s*\)#i',
            function ($m) use ($dir) {
                $segments = [];
                foreach (explode('/', trim($dir, '/').'/'.$m[2]) as $segment) {
                    if ($segment === '..') {
                        array_pop($segments);
                    } elseif ($segment !== '.' && $segment !== '') {
                        $segments[] = $segment;
                    }
                }

                return 'url('.$m[1].'/'.implode('/', $segments).$m[1].')';
            },
            $css
        ) ?? $css;
    }

    /**
     * The hero slideshow image is the largest paint, but the browser only finds it once
     * public/js/site.js builds the slideshow from data-slideshow. Ask for it straight away.
     */
    private function preloadHeroImage(string $head, string $body): string
    {
        if (! preg_match('#\sdata-slideshow="\[&quot;([^&]+)&quot;#', $body, $m)) {
            return $head;
        }
        $url = str_replace('\/', '/', $m[1]);
        if (! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return $head;
        }

        return $this->addEarly($head, '<link rel="preload" href="'.htmlspecialchars($url).'" as="image" fetchpriority="high">');
    }
}
