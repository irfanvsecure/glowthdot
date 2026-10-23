<?php

$base = dirname(__DIR__);
$public = $base.'/public';
$needed = [];

function collect(string $content): array
{
    $paths = [];
    $localize = function ($url) {
        $url = preg_replace('#^https?://(www\.)?glowthdot\.com#i', '', $url) ?? $url;
        $url = str_replace(['/wp-content/uploads/', '/wp-content/themes/', '/wp-content/plugins/', '/wp-includes/'], ['/images/', '/assets/themes/', '/assets/plugins/', '/assets/includes/'], $url);

        return $url;
    };
    if (preg_match_all('/(?:src|href|data-src|data-lazy-src)=["\']([^"\']+)["\']/i', $content, $m)) {
        foreach ($m[1] as $u) {
            $u = $localize($u);
            $p = parse_url($u, PHP_URL_PATH);
            if (is_string($p) && preg_match('#^/(images|assets)/#', $p) && preg_match('#\.(png|jpe?g|gif|webp|svg|ico|css|js|woff2?)$#i', $p)) {
                $paths[$p] = true;
            }
        }
    }
    if (preg_match_all('/srcset=["\']([^"\']+)["\']/i', $content, $m)) {
        foreach ($m[1] as $set) {
            foreach (preg_split('/\s*,\s*/', $set) ?: [] as $part) {
                $url = $localize(trim(explode(' ', trim($part))[0]));
                $p = parse_url($url, PHP_URL_PATH);
                if (is_string($p) && preg_match('#^/(images|assets)/#', $p)) {
                    $paths[$p] = true;
                }
            }
        }
    }
    if (preg_match_all('/url\((["\']?)([^)\'"]+)\1\)/i', $content, $m)) {
        foreach ($m[2] as $u) {
            if (str_starts_with($u, 'data:')) {
                continue;
            }
            $u = $localize($u);
            $p = parse_url($u, PHP_URL_PATH);
            if (is_string($p) && preg_match('#^/(images|assets)/#', $p)) {
                $paths[$p] = true;
            }
        }
    }

    return $paths;
}

foreach (glob($base.'/storage/app/live-html/*.html') ?: [] as $f) {
    $needed = array_merge($needed, collect(file_get_contents($f) ?: ''));
}
foreach (glob($public.'/images/elementor/css/*.css') ?: [] as $f) {
    $needed = array_merge($needed, collect(file_get_contents($f) ?: ''));
}

$miss = [];
foreach (array_keys($needed) as $p) {
    if (! is_file($public.$p) || filesize($public.$p) === 0) {
        $miss[] = $p;
    }
}
echo 'refs='.count($needed).' missing='.count($miss)."\n";
foreach ($miss as $p) {
    echo "$p\n";
}
