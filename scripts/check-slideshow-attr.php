<?php

$h = file_get_contents(dirname(__DIR__).'/storage/app/live-html/home.html') ?: '';
$pos = strpos($h, 'background_background');
echo 'pos='.$pos.PHP_EOL;
if ($pos !== false) {
    echo substr($h, $pos - 100, 400).PHP_EOL;
}

echo "\n--- hero fallback css present: ".(str_contains($h, 'glowth-hero-fallback') ? 'yes' : 'no').PHP_EOL;
echo 'entry-title Home: '.(preg_match('/entry-title[^>]*>\s*Home/i', $h) ? 'yes' : 'no').PHP_EOL;
echo 'hide-page-title css: '.(str_contains($h, 'glowth-hide-page-title') ? 'yes' : 'no').PHP_EOL;

$files = [
    'swiper' => dirname(__DIR__).'/public/assets/plugins/elementor/assets/lib/swiper/v8/swiper.min.js',
    'pro-frontend' => dirname(__DIR()).'/public/assets/plugins/elementor-pro/assets/js/frontend.min.js',
    'pro-handlers' => dirname(__DIR()).'/public/assets/plugins/elementor-pro/assets/js/elements-handlers.min.js',
];
foreach ($files as $k => $f) {
    echo "$k: ".(is_file($f) ? filesize($f) : 'MISSING').PHP_EOL;
}

// Improve hero fallback - target e-con that contains slideshow more reliably
// Find element id of hero container
if (preg_match('/data-id="([^"]+)"[^>]*data-settings="[^"]*background_background&quot;:&quot;slideshow/', $h, $m)
    || preg_match('/data-id="([^"]+)"[^>]*data-settings="[^"]*slideshow/', $h, $m)
    || preg_match('/data-id=\\"([^\\"]+)\\"[^>]{0,400}slideshow/', $h, $m)) {
    echo 'hero data-id: '.$m[1].PHP_EOL;
}

// Try another pattern - settings often HTML-entity encoded
if (preg_match('/data-id="([a-f0-9]+)"[^>]*data-element_type="container"[^>]*data-settings="\{&quot;background_background&quot;:&quot;slideshow/', $h, $m)) {
    echo 'hero id entity: '.$m[1].PHP_EOL;
}
