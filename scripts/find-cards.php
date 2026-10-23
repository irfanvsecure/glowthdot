<?php

$files = [
    'industries' => dirname(__DIR__).'/storage/app/live-html/industries.html',
    'beauty-salons' => dirname(__DIR__).'/storage/app/live-html/beauty-salons.html',
    'home' => dirname(__DIR__).'/storage/app/live-html/home.html',
    'industries-live' => dirname(__DIR__).'/storage/app/live-seo/industries-live.html',
    'beauty-live' => dirname(__DIR__).'/storage/app/live-seo/beauty-salons-live.html',
];

$needles = [
    'Hair Salon', 'Nail Salon', 'Beauty Salon', 'Day Spa', 'Barber', 'Lash',
    'Medical Spa', 'Dermatology', 'Brow', 'Makeup', 'Wellness', 'Aesthetic',
    'Industry', 'We serve', 'Who we help',
];

foreach ($files as $label => $path) {
    if (! is_file($path)) {
        echo "$label: missing file\n";
        continue;
    }
    $h = file_get_contents($path) ?: '';
    echo "\n==== $label (".strlen($h).") ====\n";
    foreach ($needles as $t) {
        $p = stripos($h, $t);
        echo $t.': '.($p === false ? 'NOT FOUND' : $p)."\n";
        if ($p !== false) {
            $snip = substr($h, max(0, $p - 200), 500);
            $snip = preg_replace('/\s+/', ' ', $snip);
            echo "  SNIP: $snip\n";
            break; // one snip per file from first hit
        }
    }

    // Find all h2/h3/h4 text that look like cards
    if (preg_match_all('#<(h[2-4]|div class="[^"]*elementor-heading-title[^"]*")[^>]*>(.*?)</(?:h[2-4]|div)>#is', $h, $m, PREG_SET_ORDER)) {
        echo "Headings sample:\n";
        $i = 0;
        foreach ($m as $row) {
            $text = trim(html_entity_decode(strip_tags($row[2])));
            if ($text === '' || strlen($text) > 60) {
                continue;
            }
            echo "  - $text\n";
            if (++$i >= 40) {
                break;
            }
        }
    }

    // premium addons / nested carousel / posts
    foreach (['elementor-widget-image-box', 'elementor-widget-icon-box', 'e-loop-item', 'elementor-post', 'premium-blog', 'elementor-cta', 'elementor-widget-button'] as $w) {
        $c = substr_count($h, $w);
        if ($c) {
            echo "widget $w: $c\n";
        }
    }
}
