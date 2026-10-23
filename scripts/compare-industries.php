<?php

$liveFile = dirname(__DIR__).'/storage/app/live-seo/industries-live.html';
$localFile = dirname(__DIR__).'/storage/app/live-html/industries.html';

if (! is_file($liveFile) || filesize($liveFile) < 1000) {
    $ch = curl_init('https://glowthdot.com/industries/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'GlowthMirror/1.0',
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    file_put_contents($liveFile, $body ?: '');
}

$live = file_get_contents($liveFile) ?: '';
$local = file_get_contents($localFile) ?: '';
echo 'live len '.strlen($live).' local '.strlen($local).PHP_EOL;

function normalizeHref(string $href): string
{
    $href = preg_replace('#^https?://(www\.)?glowthdot\.com#i', '', $href) ?? $href;
    $href = preg_replace('#^//(www\.)?glowthdot\.com#i', '', $href) ?? $href;

    return $href;
}

function contentLinks(string $html): array
{
    $links = [];
    if (! preg_match_all('#<a[^>]+href=(["\'])([^"\']+)\1[^>]*>(.*?)</a>#is', $html, $m, PREG_SET_ORDER)) {
        return [];
    }
    foreach ($m as $row) {
        $href = normalizeHref($row[2]);
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($row[3]))));
        if ($text === '' || strlen($text) > 80) {
            continue;
        }
        if (str_starts_with($href, '#') || str_contains($href, 'wp-') || str_contains($href, 'font')) {
            continue;
        }
        if (preg_match('#\.(css|js|png|jpe?g|gif|webp|svg|ico)$#i', $href)) {
            continue;
        }
        if (str_starts_with($href, '/images/') || str_starts_with($href, '/assets/') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'https://wa.')) {
            continue;
        }
        // skip menu noise somewhat - keep all for now
        $links[$text.' => '.$href] = true;
    }

    return array_keys($links);
}

function imageBoxCards(string $html): array
{
    $cards = [];
    // Elementor image box widget blocks
    if (preg_match_all('#elementor-widget-image-box.{0,2000}?elementor-image-box-title.{0,50}?>(.*?)</(?:h[1-6]|div|span)>#is', $html, $m, PREG_SET_ORDER)) {
        foreach ($m as $row) {
            $chunk = $row[0];
            $title = trim(html_entity_decode(strip_tags($row[1])));
            $href = null;
            if (preg_match('#href=(["\'])([^"\']+)\1#i', $chunk, $hm)) {
                $href = normalizeHref($hm[2]);
            }
            $cards[] = ['title' => $title, 'href' => $href, 'has_link' => $href !== null];
        }
    }

    return $cards;
}

$L = contentLinks($live);
$C = contentLinks($local);
echo 'live content links: '.count($L).' local: '.count($C).PHP_EOL;

// Focus on /slug/ style links that look like industry pages
function industryish(array $links): array
{
    $out = [];
    foreach ($links as $line) {
        if (preg_match('#=> (/[a-z0-9\-/]+/)#', $line, $m)) {
            $out[$m[1]] = $line;
        }
    }

    return $out;
}

$li = industryish($L);
$ci = industryish($C);
echo 'live path links: '.count($li).' local path links: '.count($ci).PHP_EOL;
$onlyLive = array_diff_key($li, $ci);
$onlyLocal = array_diff_key($ci, $li);
echo "paths only on live:\n";
foreach ($onlyLive as $p => $line) {
    echo "  $line\n";
}
echo "paths only on local:\n";
foreach ($onlyLocal as $p => $line) {
    echo "  $line\n";
}

echo "\n--- Live image-box cards ---\n";
foreach (imageBoxCards($live) as $c) {
    echo ($c['has_link'] ? '[LINK '.$c['href'].']' : '[NO LINK]').' '.$c['title']."\n";
}
echo "\n--- Local image-box cards ---\n";
foreach (imageBoxCards($local) as $c) {
    echo ($c['has_link'] ? '[LINK '.$c['href'].']' : '[NO LINK]').' '.$c['title']."\n";
}

// Also check home industries section
$home = file_get_contents(dirname(__DIR__).'/storage/app/live-html/home.html') ?: '';
echo "\n--- Home local image-box cards ---\n";
foreach (imageBoxCards($home) as $c) {
    echo ($c['has_link'] ? '[LINK '.$c['href'].']' : '[NO LINK]').' '.$c['title']."\n";
}
