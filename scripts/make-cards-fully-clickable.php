<?php

/**
 * Make industry icon-boxes fully clickable: wrap icon+content in the title's href.
 * Also verify destinations + sample images via local HTTP.
 */

$base = dirname(__DIR__);
$htmlDir = $base.'/storage/app/live-html';

function wrapBoxes(string $html): array
{
    $n = 0;
    // Match a full icon-box widget outer that contains a titled link
    $html2 = preg_replace_callback(
        '/(<div class="elementor-icon-box-wrapper">)(.*?)(<\/div>\s*<\/div>\s*<\/div>)/is',
        function ($m) use (&$n) {
            $inner = $m[2];
            if (stripos($inner, 'elementor-icon-box-title') === false) {
                return $m[0];
            }
            if (! preg_match('/elementor-icon-box-title[^>]*>\s*<a\s+href=["\']([^"\']+)["\']/i', $inner, $hm)) {
                return $m[0];
            }
            $href = $hm[1];
            // skip tel/mailto
            if (str_starts_with($href, 'tel:') || str_starts_with($href, 'mailto:') || $href === '#') {
                return $m[0];
            }
            // already has stretched link wrapper?
            if (preg_match('/class="[^"]*elementor-icon-box-wrapper[^"]*"[^>]*>\s*<a\s+class="glowth-card-link"/', $m[0])) {
                return $m[0];
            }
            // Avoid double-wrapping if outer already starts with our link
            if (str_starts_with(ltrim($inner), '<a class="glowth-card-link"')) {
                return $m[0];
            }
            $n++;

            // Remove nested <a> around title to avoid invalid nested anchors; keep title text
            $innerClean = preg_replace(
                '/(<p class="elementor-icon-box-title">)\s*<a\s+href=["\'][^"\']+["\']\s*>/i',
                '$1',
                $inner
            ) ?? $inner;
            $innerClean = preg_replace(
                '/(<\/span>)\s*<\/a>(\s*<\/p>)/i',
                '$1$2',
                $innerClean
            ) ?? $innerClean;
            // also if </a> before </p> without span
            $innerClean = preg_replace(
                '/<\/a>(\s*<\/p>)/i',
                '$1',
                $innerClean
            ) ?? $innerClean;

            return $m[1].'<a class="glowth-card-link" href="'.$href.'" style="display:block;color:inherit;text-decoration:none;">'.$innerClean.'</a>'.$m[3];
        },
        $html
    );

    return [$html2 ?? $html, $n];
}

$files = ['industries', 'home'];
foreach ($files as $slug) {
    $file = $htmlDir.'/'.$slug.'.html';
    $html = file_get_contents($file) ?: '';
    [$html, $n] = wrapBoxes($html);
    // inject tiny CSS once
    if ($n > 0 && stripos($html, 'glowth-card-link') !== false && stripos($html, '/* glowth-card-link */') === false) {
        $css = '<style>/* glowth-card-link */a.glowth-card-link{display:block;color:inherit;text-decoration:none;}a.glowth-card-link:hover .elementor-icon-box-title{text-decoration:underline;}</style>';
        $html = preg_replace('/<\/head>/i', $css.'</head>', $html, 1) ?? $html;
    }
    file_put_contents($file, $html);
    echo "$slug wrapped boxes: $n\n";
}

// HTTP verify industry destinations
$baseUrl = 'http://127.0.0.1:8003';
$slugs = [
    'arabic-beauty-salon', 'russian-beauty-salon', 'korean-beauty-salon', 'hair-salons',
    'ladies-salon-marketing', 'barber-shops', 'nail-salons', 'medical-spas', 'aesthetic-clinics',
    'dermatology-clinics', 'plastic-surgery-clinics', 'wellness-centers', 'lash-studios',
    'brow-studios', 'makeup-studios', 'hair-extension-studios', 'laser-hair-removal-clinics',
    'skin-care-clinics', 'massage-centers', 'hammam-center', 'moroccan-bath-centre',
    'cosmetic-makeup-beauty-brand', 'skincare-beauty-brand', 'hair-care-beauty-brand', 'industries',
];
$fail = 0;
foreach ($slugs as $s) {
    $ch = curl_init($baseUrl.'/'.$s.'/');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_NOBODY => true, CURLOPT_TIMEOUT => 15]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) {
        echo "FAIL /$s/ => $code\n";
        $fail++;
    }
}
echo 'Industry pages checked: '.count($slugs).', fail: '.$fail."\n";

// image samples
$imgs = [
    '/images/2026/09/clinic-instagram-cover-flat-300x157.png',
    '/images/2026/08/salon-whatsapp-leak-cover-300x252.png',
    '/images/2026/07/cropped-glowth-logo-transparent-205x48.png',
];
foreach ($imgs as $img) {
    $ch = curl_init($baseUrl.$img);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_NOBODY => true, CURLOPT_TIMEOUT => 15]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "IMG $img => $code\n";
}
