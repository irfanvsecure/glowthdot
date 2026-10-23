<?php

/**
 * Download real Elementor / Elementor Pro / related JS+CSS referenced by home.html from live site.
 */

$base = dirname(__DIR__);
$public = $base.'/public';
$home = file_get_contents($base.'/storage/app/live-html/home.html') ?: '';

$paths = [];

// From HTML src/href
if (preg_match_all('#(?:src|href)=["\'](/assets/(?:plugins|themes|includes)/[^"\']+)["\']#i', $home, $m)) {
    foreach ($m[1] as $p) {
        $paths[parse_url($p, PHP_URL_PATH) ?: $p] = true;
    }
}

// Known critical Elementor webpack chunks used by slideshow / frontend
$extra = [
    '/assets/plugins/elementor/assets/js/webpack.runtime.min.js',
    '/assets/plugins/elementor/assets/js/frontend.min.js',
    '/assets/plugins/elementor/assets/js/frontend-modules.min.js',
    '/assets/plugins/elementor-pro/assets/js/webpack-pro.runtime.min.js',
    '/assets/plugins/elementor-pro/assets/js/frontend.min.js',
    '/assets/plugins/elementor-pro/assets/js/elements-handlers.min.js',
    '/assets/plugins/elementor/assets/lib/swiper/v8/swiper.min.js',
    '/assets/plugins/elementor/assets/lib/swiper/v8/css/swiper.min.css',
];
foreach ($extra as $p) {
    $paths[$p] = true;
}

function liveUrl(string $path): string
{
    $live = $path;
    $live = preg_replace('#^/assets/themes/#', '/wp-content/themes/', $live) ?? $live;
    $live = preg_replace('#^/assets/plugins/#', '/wp-content/plugins/', $live) ?? $live;
    $live = preg_replace('#^/assets/includes/#', '/wp-includes/', $live) ?? $live;

    return 'https://glowthdot.com'.$live;
}

function download(string $path, string $public): array
{
    $full = $public.$path;
    $url = liveUrl($path);
    $dir = dirname($full);
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'GlowthMirror/1.0',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 200 && $code < 300 && is_string($body) && strlen($body) > 50) {
        // skip if identical stub and tiny
        file_put_contents($full, $body);

        return ['ok' => true, 'bytes' => strlen($body), 'url' => $url];
    }

    return ['ok' => false, 'code' => $code, 'url' => $url];
}

// Force re-download stubs that are too small
$force = [
    '/assets/plugins/elementor/assets/js/webpack.runtime.min.js',
    '/assets/plugins/elementor/assets/js/frontend.min.js',
    '/assets/plugins/elementor/assets/js/frontend-modules.min.js',
];

$ok = 0;
$fail = 0;
foreach (array_keys($paths) as $path) {
    $full = $public.$path;
    $isForce = in_array($path, $force, true);
    $tooSmall = is_file($full) && filesize($full) < 500 && str_ends_with($path, '.js');
    if (is_file($full) && filesize($full) > 500 && ! $isForce && ! $tooSmall) {
        continue; // keep existing good files
    }
    // Always refresh force list
    $r = download($path, $public);
    if ($r['ok']) {
        echo 'OK '.$path.' ('.$r['bytes'].")\n";
        $ok++;
    } else {
        echo 'FAIL '.$path.' code='.($r['code'] ?? '?').' '.$r['url']."\n";
        $fail++;
    }
}

// Parse webpack.runtime for chunk IDs and try to download common elementor chunks
$runtime = file_get_contents($public.'/assets/plugins/elementor/assets/js/webpack.runtime.min.js') ?: '';
echo 'webpack.runtime size now: '.strlen($runtime).PHP_EOL;

// Elementor 3.x stores chunks as numbered .js files like 340.min.js, background.min.js etc.
// Discover from live by reading frontend.min.js for chunk references
$frontend = file_get_contents($public.'/assets/plugins/elementor/assets/js/frontend.min.js') ?: '';
echo 'frontend.min.js size: '.strlen($frontend).PHP_EOL;

// Extract chunk filenames like "webpack-plugins.../xxx.min.js" or "340.min.js"
$chunkNames = [];
if (preg_match_all('/["\'](\d+\.min\.js)["\']/', $runtime.$frontend, $m)) {
    $chunkNames = array_unique($m[1]);
}
if (preg_match_all('/["\']([a-z0-9-]+\.min\.js)["\']/', $runtime, $m)) {
    foreach ($m[1] as $n) {
        if (! str_contains($n, 'frontend')) {
            $chunkNames[] = $n;
        }
    }
}
$chunkNames = array_unique($chunkNames);
echo 'chunk candidates: '.count($chunkNames).PHP_EOL;

foreach ($chunkNames as $name) {
    $path = '/assets/plugins/elementor/assets/js/'.$name;
    if (is_file($public.$path) && filesize($public.$path) > 200) {
        continue;
    }
    $r = download($path, $public);
    if ($r['ok']) {
        echo "CHUNK OK $name (".$r['bytes'].")\n";
        $ok++;
    }
}

// Also download elementor-pro webpack runtime chunks similarly
$proRuntimePath = '/assets/plugins/elementor-pro/assets/js/webpack-pro.runtime.min.js';
if (! is_file($public.$proRuntimePath) || filesize($public.$proRuntimePath) < 500) {
    $r = download($proRuntimePath, $public);
    echo ($r['ok'] ? 'OK' : 'FAIL')." webpack-pro.runtime\n";
}

echo "Downloaded/refreshed: $ok, failed: $fail\n";

// Verify stubs gone
foreach ($force as $p) {
    $sz = is_file($public.$p) ? filesize($public.$p) : 0;
    echo basename($p)." => $sz bytes\n";
}
