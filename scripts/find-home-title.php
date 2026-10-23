<?php

$h = file_get_contents(dirname(__DIR__).'/storage/app/live-seo/served-home.html') ?: '';
echo 'len='.strlen($h).PHP_EOL;

// body class
if (preg_match('/<body[^>]*class=["\']([^"\']+)/', $h, $m)) {
    echo 'body class: '.$m[1].PHP_EOL;
}

// find standalone Home headings
foreach (['entry-header', 'entry-title', 'page-header', 'ast-archive-description', 'ast-single-post-order', 'ast-page-title'] as $c) {
    echo "$c count: ".substr_count($h, $c).PHP_EOL;
}

// Find h1 tags
if (preg_match_all('/<h1[^>]*>(.*?)<\/h1>/is', $h, $m, PREG_SET_ORDER)) {
    echo 'h1 count: '.count($m).PHP_EOL;
    foreach ($m as $i => $row) {
        $t = trim(preg_replace('/\s+/', ' ', strip_tags($row[1])));
        echo "  h1[$i]: ".substr($t, 0, 120)."\n";
        echo '  raw attrs: '.substr($row[0], 0, 200)."\n";
    }
}

// Search for >Home< as sole content
if (preg_match_all('/>(\s*Home\s*)</i', $h, $m, PREG_OFFSET_CAPTURE)) {
    echo 'bare Home text hits: '.count($m[0]).PHP_EOL;
    foreach (array_slice($m[0], 0, 10) as $hit) {
        $pos = $hit[1];
        echo '  context: '.preg_replace('/\s+/', ' ', substr($h, max(0, $pos - 120), 250))."\n";
    }
}

// Compare with storage live-html home
$local = file_get_contents(dirname(__DIR__).'/storage/app/live-html/home.html') ?: '';
echo 'live-html home len='.strlen($local).' same='.(md5($h) === md5($local) ? 'yes' : 'no').PHP_EOL;

// Check CSS that should hide page title
foreach (['ast-page-builder-template', 'ast-no-title', 'elementor-page'] as $c) {
    echo "has $c: ".(str_contains($h, $c) ? 'yes' : 'no').PHP_EOL;
}
