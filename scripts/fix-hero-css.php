<?php

$f = dirname(__DIR__).'/storage/app/live-html/home.html';
$h = file_get_contents($f);

$new = '<style>/* glowth-hero-fallback */'
    .'#bg,.elementor-element-d2ed37a{'
    .'background-image:linear-gradient(rgba(10,5,20,.55),rgba(10,5,20,.65)),url(\'/images/2026/07/photo-1522337660859-02fbefca4702.jpg\')!important;'
    .'background-size:cover!important;background-position:center center!important;background-repeat:no-repeat!important;'
    .'}'
    .'#bg .elementor-background-slideshow,#bg .elementor-background-video-container{z-index:0;}'
    .'#bg>.e-con-inner,#bg>.elementor-container{position:relative;z-index:1;}'
    .'.nichebar .separator{color:#c6f432!important;}'
    .'</style>';

$h2 = preg_replace('#<style>/\* glowth-hero-fallback \*/.*?</style>#s', $new, $h, 1);
if ($h2 === null || $h2 === $h) {
    echo "WARN: fallback replace failed or same\n";
} else {
    file_put_contents($f, $h2);
    echo "hero CSS simplified\n";
}

// Check custom .bg{} in post-8.css
$css = file_get_contents(dirname(__DIR__).'/public/images/elementor/css/post-8.css');
if (preg_match('/\/\* Start custom CSS for container, class: \.elementor-element-d2ed37a \*\/(.*?)\/\* End custom CSS/s', $css, $m)) {
    echo "custom .bg CSS:\n".$m[1]."\n";
}

// nichebar inline in html widget
$html = file_get_contents($f);
if (preg_match('/<style[^>]*>\s*\.nichebar.*?<\/style>/is', $html, $m)) {
    echo "nichebar style block:\n".substr($m[0], 0, 800)."\n";
} elseif (preg_match('/\.nichebar\{[^}]+\}/s', $html, $m)) {
    echo "nichebar rule: ".$m[0]."\n";
} else {
    echo "no nichebar css in html\n";
}

// Ensure Content-Type charset - SiteController already sets UTF-8
echo 'has 100vh: '.(str_contains(file_get_contents($f), 'min-height:100vh') ? 'yes' : 'no')."\n";
preg_match('/class="separator">(.*?)<\/span>/s', file_get_contents($f), $sm);
echo 'sep bytes: ';
for ($i = 0; $i < strlen($sm[1]); $i++) {
    echo sprintf('%02X ', ord($sm[1][$i]));
}
echo "\n";
