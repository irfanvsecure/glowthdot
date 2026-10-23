<?php

$base = dirname(__DIR__);
$file = $base.'/storage/app/live-html/home.html';
$html = file_get_contents($file);

// Inspect separator bytes
if (preg_match('/class="separator">(.*?)<\/span>/s', $html, $m)) {
    echo 'separator raw: ';
    for ($i = 0; $i < strlen($m[1]); $i++) {
        echo sprintf('%02X ', ord($m[1][$i]));
    }
    echo PHP_EOL.'separator as utf8: '.$m[1].PHP_EOL;
}

// Live fetch for comparison
$ch = curl_init('https://glowthdot.com/');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
]);
$live = curl_exec($ch);
curl_close($ch);
if (is_string($live) && preg_match('/class="separator">(.*?)<\/span>/s', $live, $m)) {
    echo 'LIVE separator bytes: ';
    for ($i = 0; $i < strlen($m[1]); $i++) {
        echo sprintf('%02X ', ord($m[1][$i]));
    }
    echo PHP_EOL;
}

// Common mojibake fixes (UTF-8 read as Windows-1252 / ISO-8859-1 then re-saved)
$replacements = [
    // ✦ U+2726 (E2 9C A6) -> âœ¦
    "\xC3\xA2\xC2\x9C\xC2\xA6" => '✦',
    'âœ¦' => '✦',
    // sometimes ✧
    'âœ§' => '✧',
    // en dash –
    "\xC3\xA2\xE2\x82\xAC\xE2\x80\x9C" => '–',
    'â€“' => '–',
    "\xC3\xA2\xC2\x80\xC2\x93" => '–',
    // em dash —
    'â€”' => '—',
    "\xC3\xA2\xC2\x80\xC2\x94" => '—',
    // bullet / middle
    'â€¢' => '•',
    // arrows / fancy
    'â†’' => '→',
    'â€™' => '’',
    'â€˜' => '‘',
    'â€œ' => '“',
    'â€' => '”',
    'Â ' => ' ',
    'Â·' => '·',
    'Â©' => '©',
    'Â®' => '®',
    // topbar gift etc
    'ðŸŽ' => '🎁',
];

$count = 0;
foreach ($replacements as $bad => $good) {
    $n = 0;
    $html = str_replace($bad, $good, $html, $n);
    if ($n > 0) {
        echo "fixed '$bad' => '$good' x$n\n";
        $count += $n;
    }
}

// Also fix separator by regex to always be ✦
$html = preg_replace(
    '/(<span class="separator">)(.*?)(<\/span>)/s',
    '$1✦$3',
    $html,
    -1,
    $sepN
);
echo "separator spans normalized: $sepN\n";

// Fix hero spacing: our fallback forced min-height:100vh which creates huge bottom gap
// Use softer min-height matching typical hero, keep background image
$oldFallback = null;
if (preg_match('#<style>/\* glowth-hero-fallback \*/.*?</style>#s', $html, $m)) {
    $oldFallback = $m[0];
}
$newFallback = '<style>/* glowth-hero-fallback */'
    .'#bg,.elementor-element-d2ed37a{'
    .'background-image:linear-gradient(rgba(10,5,20,.55),rgba(10,5,20,.65)),url(\'/images/2026/07/photo-1522337660859-02fbefca4702.jpg\')!important;'
    .'background-size:cover!important;background-position:center center!important;background-repeat:no-repeat!important;'
    .'min-height:0!important;}'
    /* Elementor often sets large padding; tighten only vertical padding on hero shell */
    .'#bg.e-con > .e-con-inner,#bg > .e-con-inner{'
    .'padding-top:clamp(4.5rem,8vh,6rem)!important;'
    .'padding-bottom:clamp(2rem,4vh,3rem)!important;}'
    .'#bg .elementor-background-slideshow,#bg .elementor-background-video-container{z-index:0;}'
    .'#bg > .e-con-inner,#bg > .elementor-container{position:relative;z-index:1;}'
    /* niche ticker height */
    .'.nichebar{padding-top:0.65rem!important;padding-bottom:0.65rem!important;}'
    .'.nichebar .ticker{line-height:1.2!important;}'
    .'.nichebar .separator{color:#c6f432!important;font-size:0.85em;}'
    .'</style>';

if ($oldFallback) {
    $html = str_replace($oldFallback, $newFallback, $html);
    echo "hero fallback CSS updated (removed 100vh)\n";
} elseif (! str_contains($html, 'glowth-hero-fallback')) {
    $html = preg_replace('/<\/head>/i', $newFallback.'</head>', $html, 1);
    echo "hero fallback CSS inserted\n";
}

file_put_contents($file, $html);

// Verify
$html = file_get_contents($file);
preg_match('/class="separator">(.*?)<\/span>/s', $html, $m);
echo 'after separator: ';
for ($i = 0; $i < strlen($m[1]); $i++) {
    echo sprintf('%02X ', ord($m[1][$i]));
}
echo ' => '.$m[1].PHP_EOL;

if (preg_match('/Get My SEO Audit(.{1,12})Free/', $html, $m)) {
    echo 'audit CTA middle: ';
    for ($i = 0; $i < strlen($m[1]); $i++) {
        echo sprintf('%02X ', ord($m[1][$i]));
    }
    echo PHP_EOL;
}

echo "Done. replacements≈$count\n";
