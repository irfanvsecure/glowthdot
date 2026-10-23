<?php

$base = dirname(__DIR__);
$htmlDir = $base.'/storage/app/live-html';
$public = $base.'/public';

$pages = [
    'industries',
    'beauty-salons',
    'aesthetic-clinics',
    'home',
    'hair-salons',
    'nail-salons',
    'day-spas',
    'medical-spas',
    'dermatology-clinics',
    'laser-hair-removal-clinics',
    'barber-shops',
    'lash-studios',
    'brow-studios',
    'makeup-studios',
    'beauty-academies',
    'wellness-centers',
    'ecommerce-for-beauty',
];

function extractInternalHrefs(string $html): array
{
    $out = [];
    if (preg_match_all('~href=["\'](/[^"\']*)["\']~i', $html, $m)) {
        foreach ($m[1] as $h) {
            if (preg_match('~\.(css|js|png|jpe?g|gif|webp|svg|ico|woff2?|map|xml|txt)$~i', $h)) {
                continue;
            }
            if (str_starts_with($h, '/images/') || str_starts_with($h, '/assets/')) {
                continue;
            }
            $path = parse_url($h, PHP_URL_PATH) ?? $h;
            $out[$path] = ($out[$path] ?? 0) + 1;
        }
    }

    return $out;
}

function extractImages(string $html): array
{
    $out = [];
    if (preg_match_all('~(?:src|data-src|data-lazy-src)=["\'](/[^"\']+)["\']~i', $html, $m)) {
        foreach ($m[1] as $s) {
            $path = parse_url($s, PHP_URL_PATH) ?? $s;
            if (str_starts_with($path, '/images/') || str_starts_with($path, '/assets/')) {
                $out[$path] = true;
            }
        }
    }
    // background images in style
    if (preg_match_all('~url\((["\']?)(/[^)\'"]+)\1\)~i', $html, $m)) {
        foreach ($m[2] as $s) {
            $path = parse_url($s, PHP_URL_PATH) ?? $s;
            if (str_starts_with($path, '/images/') || str_starts_with($path, '/assets/')) {
                $out[$path] = true;
            }
        }
    }

    return array_keys($out);
}

// Also find Elementor cards: widget-image-box, posts, loop that might lack links
function findUnlinkedCards(string $html): array
{
    $hints = [];
    // image-box titles
    if (preg_match_all('~elementor-image-box-title[^>]*>\s*(?:<a[^>]*>)?([^<]+)~i', $html, $m)) {
        foreach ($m[0] as $i => $full) {
            $hasLink = stripos($full, '<a ') !== false;
            $title = trim(html_entity_decode(strip_tags($m[1][$i])));
            if ($title !== '') {
                $hints[] = ['type' => 'image-box', 'title' => $title, 'linked' => $hasLink];
            }
        }
    }
    // heading widgets that look like card titles with sibling links?
    if (preg_match_all('~class="[^"]*elementor-cta__title[^"]*"[^>]*>([^<]+)~i', $html, $m)) {
        foreach ($m[1] as $t) {
            $hints[] = ['type' => 'cta', 'title' => trim($t), 'linked' => null];
        }
    }

    return $hints;
}

$avail = [];
foreach (glob($htmlDir.'/*.html') ?: [] as $f) {
    $avail[basename($f, '.html')] = true;
}

$missingImgs = [];
$missingLinks = [];

foreach ($pages as $slug) {
    $file = $htmlDir.'/'.$slug.'.html';
    if (! is_file($file)) {
        echo "MISSING PAGE FILE: $slug\n";
        continue;
    }
    $html = file_get_contents($file) ?: '';
    echo "\n=== $slug (".strlen($html)." bytes) ===\n";

    $hrefs = extractInternalHrefs($html);
    foreach ($hrefs as $path => $c) {
        $p = trim($path, '/');
        if ($p === '') {
            continue;
        }
        $map = [
            'privacy-policy' => 'privacy-policy-2',
            'free-audit' => 'contact-us',
            'get-a-free-audit' => 'contact-us',
            'our-services' => 'services',
        ];
        $target = $map[$p] ?? str_replace('/', '-', $p);
        // strip hash from target
        $target = explode('#', $target)[0];
        if ($target === '') {
            continue;
        }
        if (! isset($avail[$target])) {
            $missingLinks[$slug][] = $path;
        }
    }
    if (! empty($missingLinks[$slug])) {
        echo 'Broken internal: '.implode(', ', array_unique($missingLinks[$slug]))."\n";
    } else {
        echo "Broken internal: none\n";
    }

    $imgs = extractImages($html);
    $miss = [];
    foreach ($imgs as $img) {
        if (! is_file($public.$img)) {
            $miss[] = $img;
            $missingImgs[$img] = true;
        }
    }
    echo 'Images refs: '.count($imgs).', missing files: '.count($miss)."\n";
    foreach (array_slice($miss, 0, 15) as $m) {
        echo "  IMG MISS: $m\n";
    }

    $cards = findUnlinkedCards($html);
    $unlinked = array_filter($cards, fn ($c) => $c['linked'] === false);
    if ($unlinked) {
        echo 'Unlinked image-boxes: '.count($unlinked)."\n";
        foreach (array_slice($unlinked, 0, 20) as $c) {
            echo "  CARD: {$c['title']}\n";
        }
    }
}

echo "\n=== TOTAL unique missing images: ".count($missingImgs)." ===\n";
foreach (array_keys($missingImgs) as $img) {
    echo "$img\n";
}
file_put_contents($base.'/storage/app/live-seo/missing-images.json', json_encode(array_keys($missingImgs), JSON_PRETTY_PRINT));
