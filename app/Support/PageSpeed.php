<?php

namespace App\Support;

/**
 * Speeds up the first paint of the mirrored WordPress pages without touching the snapshots:
 * self-hosted fonts, one stylesheet instead of ~25, an early hero image request and a
 * logo srcset that suits phone screens.
 */
class PageSpeed
{
    /** Google Fonts families served from public/fonts (see public/css/fonts.css) */
    private const SELF_HOSTED_FONTS = ['Inter', 'Space Grotesk', 'Roboto', 'Roboto Slab'];

    /** Fonts the first screen needs, fetched in parallel with the stylesheet */
    private const PRELOAD_FONTS = ['/fonts/inter-latin.woff2', '/fonts/space-grotesk-latin.woff2'];

    private const BUNDLE_DIR = '/css/bundle';

    public function optimize(string $html): string
    {
        $parts = preg_split('#(</head>)#i', $html, 2, PREG_SPLIT_DELIM_CAPTURE);
        if (count($parts) !== 3) {
            return $html;
        }
        [$head, $close, $body] = $parts;

        $head = $this->selfHostFonts($head);
        $head = $this->bundleStyles($head);
        $head = $this->preloadHeroImage($head, $body);
        $body = $this->trimLogoSrcset($body);

        return $head.$close.$body;
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
     * The theme asks Google for every weight and italic of four families (five requests on
     * another origin, then the font files). The variable woff2 files cover all weights in use.
     */
    private function selfHostFonts(string $head): string
    {
        $replaced = false;

        $head = preg_replace_callback(
            "#<link rel=['\"]stylesheet['\"][^>]*href=['\"]https://fonts\.googleapis\.com/css\?family=([^'\":&]+)[^>]*>\s*#i",
            function ($m) use (&$replaced) {
                if (! in_array(str_replace('+', ' ', urldecode($m[1])), self::SELF_HOSTED_FONTS, true)) {
                    return $m[0];
                }
                // The first one becomes the local font stylesheet, so it keeps its place in the cascade
                $link = $replaced ? '' : "<link rel='stylesheet' id='glowth-fonts-css' href='/css/fonts.css' media='all' />\n";
                $replaced = true;

                return $link;
            },
            $head
        ) ?? $head;

        if (! $replaced) {
            return $head;
        }

        // Preconnects to Google are wasted connections once nothing is loaded from there
        if (! str_contains($head, 'fonts.googleapis.com/css')) {
            $head = preg_replace("#<link[^>]*(?:rel=['\"](?:preconnect|dns-prefetch)['\"][^>]*href=['\"](?:https:)?//fonts\.(?:googleapis|gstatic)\.com['\"]|href=['\"](?:https:)?//fonts\.(?:googleapis|gstatic)\.com['\"][^>]*rel=['\"](?:preconnect|dns-prefetch)['\"])[^>]*>\s*#i", '', $head) ?? $head;
        }

        $preload = '';
        foreach (self::PRELOAD_FONTS as $font) {
            $preload .= '<link rel="preload" href="'.$font.'" as="font" type="font/woff2" crossorigin>';
        }

        return $this->addEarly($head, $preload);
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
     * The hero slideshow image is the largest paint, but the browser only finds it inside CSS
     * (or once Elementor's JS builds the slideshow). Ask for it straight away.
     */
    private function preloadHeroImage(string $head, string $body): string
    {
        if (! preg_match('#background_slideshow_gallery&quot;:\[\{&quot;id&quot;:\d+,&quot;url&quot;:&quot;([^&]+)&quot;#', $body, $m)) {
            return $head;
        }
        $url = str_replace('\/', '/', $m[1]);
        if (! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return $head;
        }

        return $this->addEarly($head, '<link rel="preload" href="'.htmlspecialchars($url).'" as="image" fetchpriority="high">');
    }

    /**
     * WordPress offers the 205px header logo in sizes up to 1743px; a 1.75x phone screen picks
     * the 768px file. Offer the 1x and 2x files only.
     */
    private function trimLogoSrcset(string $body): string
    {
        $small = '/images/2026/07/cropped-glowth-logo-transparent-205x48.png';
        $double = '/images/2026/07/cropped-glowth-logo-transparent-410x95.png';
        if (! is_file(public_path($double))) {
            return $body;
        }

        return preg_replace_callback(
            '#<img\b[^>]*class="custom-logo"[^>]*>#i',
            fn ($m) => str_contains($m[0], $small)
                ? preg_replace('#\bsrcset="[^"]*"#', 'srcset="'.$small.' 205w, '.$double.' 410w"', $m[0])
                : $m[0],
            $body
        ) ?? $body;
    }
}
