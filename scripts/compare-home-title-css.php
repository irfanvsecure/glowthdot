<?php

$live = file_get_contents(dirname(__DIR__).'/storage/app/live-seo/live-home-now.html') ?: '';
$local = file_get_contents(dirname(__DIR__).'/storage/app/live-html/home.html') ?: '';

echo "Live has entry-title Home: ".(preg_match('/entry-title[^>]*>\s*Home/i', $live) ? 'yes' : 'no').PHP_EOL;
if (preg_match('/<body[^>]*class=["\']([^"\']+)/', $live, $m)) {
    echo 'Live body: '.$m[1].PHP_EOL;
}

// CSS rules that hide .entry-header / .entry-title on page builder
$cssFiles = [
    dirname(__DIR__).'/public/assets/themes/astra/assets/css/minified/main.min.css',
    dirname(__DIR__).'/public/images/elementor/css/post-8.css',
    dirname(__DIR__).'/public/images/elementor/css/post-6.css',
];

foreach ($cssFiles as $f) {
    if (! is_file($f)) {
        echo basename($f)." MISSING\n";
        continue;
    }
    $c = file_get_contents($f) ?: '';
    echo basename($f).' size='.strlen($c).PHP_EOL;
    foreach (['entry-header', 'entry-title', 'ast-page-builder', 'ast-no-title', 'display:none'] as $needle) {
        // count relevant
    }
    // extract rules mentioning entry-header or entry-title
    if (preg_match_all('/[^{}]*entry-(?:header|title)[^{]*\{[^}]+\}/i', $c, $m)) {
        echo '  rules with entry-header/title: '.count($m[0]).PHP_EOL;
        foreach (array_slice($m[0], 0, 15) as $r) {
            echo '  '.$r."\n";
        }
    }
}

// Inline styles in local home
if (preg_match_all('/entry-(?:header|title)[^<{]*\{[^}]+\}/i', $local, $m)) {
    echo "Inline in HTML:\n";
    foreach ($m[0] as $r) {
        echo '  '.$r."\n";
    }
}

// Check if astra has .ast-page-builder-template .entry-header{display:none}
$main = file_get_contents($cssFiles[0]) ?: '';
if (preg_match('/ast-page-builder-template[^}]*entry-header[^}]*\}/i', $main, $m)) {
    echo "FOUND: ".$m[0]."\n";
}
if (preg_match_all('/\.ast-page-builder-template[^{]*\{[^}]*\}/i', $main, $m)) {
    echo 'ast-page-builder-template rules: '.count($m[0]).PHP_EOL;
    foreach (array_slice($m[0], 0, 10) as $r) {
        echo '  '.$r."\n";
    }
}

// Hero background on live vs local
foreach (['live' => $live, 'local' => $local] as $label => $html) {
    echo "\n=== $label hero backgrounds ===\n";
    if (preg_match_all('/background(?:-image)?\s*:\s*url\(([^)]+)\)/i', $html, $m)) {
        foreach (array_unique($m[1]) as $u) {
            echo "  $u\n";
        }
    }
    // elementor background slideshow / overlay
    if (preg_match('/data-settings="([^"]*background_background[^"]*)"/i', $html, $m)) {
        echo '  data-settings sample: '.substr(html_entity_decode($m[1]), 0, 300)."\n";
    }
}
