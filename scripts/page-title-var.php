<?php

$c = file_get_contents(dirname(__DIR__).'/public/assets/plugins/elementor/assets/css/frontend.min.css') ?: '';
if (preg_match_all('/.{0,100}page-title-display.{0,100}/', $c, $m)) {
    foreach ($m[0] as $x) {
        echo $x.PHP_EOL;
    }
}

$l = file_get_contents(dirname(__DIR__).'/storage/app/live-seo/live-home-now.html') ?: '';
echo 'in live html: '.(stripos($l, 'page-title-display') !== false ? 'yes' : 'no').PHP_EOL;

// Elementor might inject via :root in kit - check all css for --page-title-display:
foreach (glob(dirname(__DIR__).'/public/images/elementor/css/*.css') ?: [] as $f) {
    $x = file_get_contents($f) ?: '';
    if (str_contains($x, '--page-title-display')) {
        echo basename($f).":\n";
        if (preg_match_all('/.{0,60}--page-title-display.{0,40}/', $x, $mm)) {
            foreach ($mm[0] as $r) {
                echo "  $r\n";
            }
        }
    }
}

// On live WP, hide via .elementor-page h1.entry-title - check
foreach (['live' => $l, 'local' => file_get_contents(dirname(__DIR__).'/storage/app/live-html/home.html')] as $label => $html) {
    // Is there inline style hiding entry-header?
    if (preg_match('/entry-header[^"]{0,40}style=["\'][^"\']*display\s*:\s*none/i', $html)) {
        echo "$label has inline hide\n";
    }
}

// Fix: set --page-title-display:none in post-6.css and/or remove entry-header from HTML
echo "\nWill fix by hiding h1.entry-title and ensuring slideshow images work.\n";

// List all slideshow gallery urls from local home
$home = file_get_contents(dirname(__DIR__).'/storage/app/live-html/home.html') ?: '';
if (preg_match('/background_slideshow_gallery":(\[.*?\])/s', $home, $m)) {
    $json = stripslashes($m[1]);
    // unescape for html entity
    $json = html_entity_decode($m[0]);
}
if (preg_match('/"background_slideshow_gallery":(\[[^\]]+\])/', $home, $m)) {
    echo "gallery raw: ".$m[1].PHP_EOL;
    if (preg_match_all('#/images/[^"\\\\]+\.(?:jpg|jpeg|png|webp)#i', $m[1], $imgs)) {
        foreach ($imgs[0] as $img) {
            $img = stripslashes($img);
            $full = dirname(__DIR__).'/public'.$img;
            echo "$img exists=".(is_file($full) ? 'yes' : 'no').' size='.(is_file($full) ? filesize($full) : 0).PHP_EOL;
        }
    }
}

// Check elementor background slideshow JS module
$mods = dirname(__DIR__).'/public/assets/plugins/elementor/assets/js';
foreach (glob($mods.'/*slideshow*') ?: [] as $f) {
    echo 'js: '.basename($f).PHP_EOL;
}
foreach (glob($mods.'/**/*slideshow*') ?: [] as $f) {
    echo 'js2: '.str_replace(dirname(__DIR__).'/public', '', $f).PHP_EOL;
}
