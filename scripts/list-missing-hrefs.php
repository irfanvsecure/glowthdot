<?php

$dir = dirname(__DIR__).'/storage/app/live-html';
$avail = [];
foreach (glob($dir.'/*.html') ?: [] as $f) {
    $avail[basename($f, '.html')] = 1;
}
$hrefs = [];
foreach (glob($dir.'/*.html') ?: [] as $f) {
    $html = file_get_contents($f) ?: '';
    if (preg_match_all('~href=["\'](/[^"\'?]*)["\']~', $html, $m)) {
        foreach ($m[1] as $h) {
            if (preg_match('#\.(css|js|png|jpg|jpeg|gif|webp|svg|ico|woff2?|xml|txt|map)$#i', $h)) {
                continue;
            }
            if (str_starts_with($h, '/images/') || str_starts_with($h, '/assets/') || str_starts_with($h, '/css/') || str_starts_with($h, '/js/')) {
                continue;
            }
            $hrefs[$h] = ($hrefs[$h] ?? 0) + 1;
        }
    }
}
ksort($hrefs);
$map = [
    'privacy-policy' => 'privacy-policy-2',
    'free-audit' => 'contact-us',
    'get-a-free-audit' => 'contact-us',
    'our-services' => 'services',
];
$missing = [];
foreach ($hrefs as $h => $c) {
    $p = trim($h, '/');
    if ($p === '' || $p === '#') {
        continue;
    }
    if (isset($map[$p])) {
        continue;
    }
    $slug = str_replace('/', '-', $p);
    if (! isset($avail[$slug])) {
        $missing[$h] = $c;
    }
}
echo 'Unique internal paths: '.count($hrefs)."\n";
echo 'Missing targets: '.count($missing)."\n";
foreach ($missing as $h => $c) {
    echo "  $h ($c)\n";
}
