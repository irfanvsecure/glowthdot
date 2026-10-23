<?php

/**
 * Fix visible "Home" page title + ensure hero slideshow images/CSS work.
 */

$base = dirname(__DIR__);
$htmlDir = $base.'/storage/app/live-html';
$public = $base.'/public';

// 1) Set page title hidden in Elementor kit CSS (matches Elementor "Hide Title")
$kit = $public.'/images/elementor/css/post-6.css';
$kitCss = file_get_contents($kit) ?: '';
if (! str_contains($kitCss, '--page-title-display:none') && ! str_contains($kitCss, '--page-title-display: none')) {
    // Prepend root override
    $kitCss = ':root{--page-title-display:none!important;}'.$kitCss;
    // Also hard-hide for safety
    if (! str_contains($kitCss, 'h1.entry-title{display:none')) {
        $kitCss .= 'h1.entry-title,.entry-header.ast-header-without-markup{display:none!important;}';
    }
    file_put_contents($kit, $kitCss);
    echo "Updated post-6.css to hide page title\n";
} else {
    echo "post-6.css already hides title\n";
}

// 2) Also inject into every live-html page head (belt and suspenders) + strip entry-header Home on builder pages
$fixedPages = 0;
$stripped = 0;
foreach (glob($htmlDir.'/*.html') ?: [] as $file) {
    $html = file_get_contents($file) ?: '';
    $orig = $html;

    // Inject CSS once
    if (! str_contains($html, '/* glowth-hide-page-title */')) {
        $css = '<style>/* glowth-hide-page-title */:root{--page-title-display:none!important;}h1.entry-title,.entry-header.ast-header-without-markup{display:none!important;}</style>';
        $html = preg_replace('/<\/head>/i', $css.'</head>', $html, 1) ?? $html;
    }

    // Remove the Astra entry-header that only contains Home (or page name) when Elementor content follows
    $html2 = preg_replace(
        '/<header class="entry-header[^"]*ast-header-without-markup[^"]*"\s*>\s*<h1 class="entry-title"[^>]*>.*?<\/h1>\s*<\/header>\s*(?:<!--[^>]*-->\s*)?/is',
        '',
        $html,
        -1,
        $count
    );
    if ($html2 !== null) {
        $html = $html2;
        $stripped += $count;
    }

    if ($html !== $orig) {
        file_put_contents($file, $html);
        $fixedPages++;
    }
}
echo "Pages updated: $fixedPages, entry-headers stripped: $stripped\n";

// 3) Ensure slideshow hero images exist (download if missing / empty)
$home = file_get_contents($htmlDir.'/home.html') ?: '';
$imgs = [];
if (preg_match_all('#/images/2026/07/photo-[a-z0-9-]+\.jpg#i', $home, $m)) {
    $imgs = array_unique($m[0]);
}
// Also from data-settings gallery specifically
if (preg_match('/background_slideshow_gallery":(\[[^\]]+\])/', $home, $m)) {
    if (preg_match_all('#\\\\?/images\\\\?/[^"\\\\]+\.(?:jpg|jpeg|png|webp)#i', $m[1], $mm)) {
        foreach ($mm[0] as $p) {
            $p = str_replace('\\/', '/', $p);
            $p = stripslashes($p);
            if (! str_starts_with($p, '/')) {
                $p = '/'.$p;
            }
            $imgs[] = $p;
        }
    }
}
$imgs = array_unique($imgs);

function fetchLive(string $localPath, string $public): bool
{
    $full = $public.$localPath;
    if (is_file($full) && filesize($full) > 1000) {
        return true;
    }
    $live = 'https://glowthdot.com'.preg_replace('#^/images/#', '/wp-content/uploads/', $localPath);
    $dir = dirname($full);
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $ch = curl_init($live);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'GlowthMirror/1.0',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 200 && $code < 300 && is_string($body) && strlen($body) > 500) {
        file_put_contents($full, $body);
        echo "Downloaded $localPath (".strlen($body).")\n";

        return true;
    }
    echo "FAIL $localPath code=$code\n";

    return false;
}

echo "Hero-related images:\n";
foreach ($imgs as $img) {
    $ok = fetchLive($img, $public);
    echo "  $img => ".($ok ? 'ok' : 'missing')."\n";
}

// 4) Check Elementor background handler chunk exists (webpack)
$jsDir = $public.'/assets/plugins/elementor/assets/js';
$neededChunks = [];
// Read webpack.runtime for chunk map? Or just list all js and download missing from live
$frontend = file_get_contents($jsDir.'/frontend.min.js') ?: '';
// Look for background slideshow references in HTML scripts
if (preg_match_all('#/assets/plugins/elementor/assets/js/[^"\']+\.js#', $home, $m)) {
    foreach ($m[0] as $p) {
        $neededChunks[$p] = true;
    }
}

// Download any missing elementor/pro JS referenced
foreach (array_keys($neededChunks) as $p) {
    if (! is_file($public.$p)) {
        $live = 'https://glowthdot.com'.str_replace('/assets/plugins/', '/wp-content/plugins/', $p);
        $dir = dirname($public.$p);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $ch = curl_init($live);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200 && is_string($body)) {
            file_put_contents($public.$p, $body);
            echo "DL JS $p\n";
        } else {
            echo "FAIL JS $p\n";
        }
    }
}

// 5) Add CSS fallback hero background if slideshow JS fails — first gallery image
$fallbackImg = '/images/2026/07/photo-1522337660859-02fbefca4702.jpg';
if (is_file($public.$fallbackImg) && ! str_contains($home, '/* glowth-hero-fallback */')) {
    // Target the first e-con with slideshow data-settings - add style tag
    $css = '<style>/* glowth-hero-fallback */'
        .'.elementor-element[data-settings*=\'"background_background":"slideshow"\']{'
        .'background-image:linear-gradient(rgba(0,0,0,.55),rgba(0,0,0,.55)),url(\''.$fallbackImg.'\')!important;'
        .'background-size:cover!important;background-position:center!important;}'
        .'</style>';
    $home = preg_replace('/<\/head>/i', $css.'</head>', $home, 1) ?? $home;
    file_put_contents($htmlDir.'/home.html', $home);
    echo "Added hero CSS fallback background\n";
}

echo "Done.\n";
