<?php

$base = 'http://127.0.0.1:8003';
$htmlDir = dirname(__DIR__).'/storage/app/live-html';

function req(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => false,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $headers = is_string($raw) ? substr($raw, 0, $headerSize) : '';
    $body = is_string($raw) ? substr($raw, $headerSize) : '';

    return [$code, $headers, $body];
}

$paths = [];
foreach (glob($htmlDir.'/*.html') ?: [] as $file) {
    $slug = basename($file, '.html');
    if ($slug === 'home') {
        $paths[] = '/';
        continue;
    }
    if (str_starts_with($slug, 'category-')) {
        $rest = substr($slug, 9);
        // page-2: clinic-marketing-page-2
        if (preg_match('#^(clinic-marketing|salon-marketing)-page-(\d+)$#', $rest, $m)) {
            $paths[] = '/category/'.$m[1].'/page/'.$m[2].'/';
        } else {
            $paths[] = '/category/'.$rest.'/';
        }
        continue;
    }
    if (str_starts_with($slug, 'author-')) {
        $paths[] = '/author/'.substr($slug, 7).'/';
        continue;
    }
    $paths[] = '/'.$slug.'/';
}

$extra = [
    '/robots.txt',
    '/sitemap.xml',
    '/sitemap_index.xml',
    '/page-sitemap.xml',
    '/post-sitemap.xml',
    '/category-sitemap.xml',
    '/favicon.ico',
    '/free-audit',
    '/get-a-free-audit',
    '/privacy-policy',
    '/our-services',
    '/this-page-does-not-exist-xyz',
];

$fail = [];
$ok = 0;
foreach (array_merge($paths, $extra) as $path) {
    [$code, $headers, $body] = req($base.$path);
    $expectOk = ! str_contains($path, 'does-not-exist');
    $expectRedirect = in_array($path, ['/free-audit', '/get-a-free-audit', '/privacy-policy', '/our-services'], true);

    if ($expectRedirect) {
        if ($code >= 300 && $code < 400) {
            $ok++;
            continue;
        }
        $fail[] = "$path => $code (expected redirect)";
        continue;
    }
    if (! $expectOk) {
        if ($code === 404) {
            $ok++;
            continue;
        }
        $fail[] = "$path => $code (expected 404)";
        continue;
    }
    if ($code === 200) {
        $ok++;
        continue;
    }
    $fail[] = "$path => $code";
}

echo "OK: $ok\n";
echo 'FAIL: '.count($fail)."\n";
foreach ($fail as $f) {
    echo "  $f\n";
}
