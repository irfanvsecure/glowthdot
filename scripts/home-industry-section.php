<?php

$home = file_get_contents(dirname(__DIR__).'/storage/app/live-html/home.html') ?: '';

$pos = stripos($home, 'Beauty industries we specialize');
echo "Heading pos: $pos\n";
if ($pos === false) {
    exit;
}

$chunk = substr($home, $pos, 25000);
// Find all links and titles in this section
echo "=== Links in industry section chunk ===\n";
if (preg_match_all('/href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $chunk, $m, PREG_SET_ORDER)) {
    foreach ($m as $row) {
        $t = trim(preg_replace('/\s+/', ' ', strip_tags($row[2])) ?? '');
        if ($t === '' || strlen($t) > 60) {
            continue;
        }
        echo $row[1].' => '.$t."\n";
    }
}

echo "\n=== Images in industry section ===\n";
if (preg_match_all('/<img[^>]+>/i', $chunk, $m)) {
    foreach ($m[0] as $img) {
        if (preg_match('/src=["\']([^"\']+)["\']/', $img, $sm)) {
            echo $sm[1]."\n";
        }
    }
}

echo "\n=== Widget types nearby ===\n";
if (preg_match_all('/data-widget_type="([^"]+)"/', $chunk, $m)) {
    print_r(array_count_values($m[1]));
}

// Maybe industries are background on containers - look for e-con with link
echo "\n=== Container data-settings with url ===\n";
if (preg_match_all('/data-settings="(\{[^"]*url[^"]*\})"/i', $chunk, $m)) {
    foreach (array_slice($m[1], 0, 20) as $s) {
        echo html_entity_decode($s)."\n";
    }
}

// Check nested carousel slides
echo "\n=== nested-carousel / slides count ===\n";
echo 'swiper-slide: '.substr_count($chunk, 'swiper-slide')."\n";
echo 'elementor-icon-box: '.substr_count($chunk, 'elementor-icon-box')."\n";

// Search whole home for day-spas, beauty-salons card links
foreach (['day-spas', 'beauty-salons', 'nail-salons', 'barber-shops', 'medical-spas', 'industries'] as $s) {
    echo "$s href count: ".substr_count($home, '/'.$s.'/')."\n";
}
