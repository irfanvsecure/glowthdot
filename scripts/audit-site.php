<?php

/**
 * Full site audit: internal links, assets, SEO tags.
 * Usage: php scripts/audit-site.php
 */

$base = dirname(__DIR__);
$htmlDir = $base.'/storage/app/live-html';
$public = $base.'/public';
$report = [
    'pages' => 0,
    'missing_html' => [],
    'broken_links' => [],
    'broken_assets' => [],
    'missing_seo' => [],
    'external_glowth' => [],
    'link_targets' => [],
];

$available = [];
foreach (glob($htmlDir.'/*.html') ?: [] as $f) {
    $available[basename($f, '.html')] = true;
}

function slugFromHref(string $href): ?string
{
    $href = trim($href);
    if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
        return null;
    }
    // skip pure anchors / query only
    if (preg_match('#^(https?:)?//#i', $href) && ! preg_match('#glowthdot\.com#i', $href) && ! str_starts_with($href, '/')) {
        return null; // external
    }
    // normalize glowth absolute or protocol-relative
    $href = preg_replace('#^https?://(www\.)?glowthdot\.com#i', '', $href) ?? $href;
    $href = preg_replace('#^//(www\.)?glowthdot\.com#i', '', $href) ?? $href;

    if (! str_starts_with($href, '/')) {
        return null; // relative weirdness skip
    }

    // strip query/hash
    $path = parse_url($href, PHP_URL_PATH) ?? $href;
    $path = rawurldecode($path);
    $path = trim($path, '/');

    // static files / known non-html
    if ($path === '') {
        return 'home';
    }
    if (preg_match('#\.(css|js|jpg|jpeg|png|gif|webp|svg|ico|woff2?|ttf|eot|map|xml|txt|json|pdf)$#i', $path)) {
        return null;
    }
    if (str_starts_with($path, 'images/') || str_starts_with($path, 'assets/') || str_starts_with($path, 'wp-content/') || str_starts_with($path, 'wp-includes/')) {
        return null;
    }
    if ($path === 'feed' || str_starts_with($path, 'feed/') || str_starts_with($path, 'wp-json') || str_starts_with($path, '?')) {
        return null;
    }

    // redirects we handle
    $redirects = [
        'privacy-policy' => 'privacy-policy-2',
        'free-audit' => 'contact-us',
        'get-a-free-audit' => 'contact-us',
        'our-services' => 'services',
    ];
    if (isset($redirects[$path])) {
        return $redirects[$path];
    }

    return str_replace('/', '-', $path);
}

function assetPath(string $src, string $public): ?string
{
    $src = trim($src);
    if ($src === '' || str_starts_with($src, 'data:') || str_starts_with($src, '#')) {
        return null;
    }
    $src = preg_replace('#^https?://(www\.)?glowthdot\.com#i', '', $src) ?? $src;
    $src = preg_replace('#^//(www\.)?glowthdot\.com#i', '', $src) ?? $src;
    if (! str_starts_with($src, '/')) {
        return null;
    }
    $path = parse_url($src, PHP_URL_PATH) ?? $src;
    $path = rawurldecode($path);
    // only check local public assets
    if (! preg_match('#^/(images|assets|css|js|favicon)#', $path)) {
        return null;
    }
    $full = $public.$path;
    // try without query
    return $full;
}

foreach (glob($htmlDir.'/*.html') ?: [] as $file) {
    $slug = basename($file, '.html');
    $report['pages']++;
    $html = file_get_contents($file) ?: '';

    // SEO checks
    $seoIssues = [];
    if (! preg_match('#<title>[^<]+</title>#i', $html)) {
        $seoIssues[] = 'missing title';
    }
    if (! preg_match('#name=["\']description["\']#i', $html)) {
        $seoIssues[] = 'missing meta description';
    }
    if (! preg_match('#rel=["\']canonical["\']#i', $html)) {
        $seoIssues[] = 'missing canonical';
    }
    if (! preg_match('#application/ld\+json#i', $html)) {
        $seoIssues[] = 'missing ld+json';
    }
    if ($seoIssues) {
        $report['missing_seo'][$slug] = $seoIssues;
    }

    // leftover absolute glowth URLs (should be relative)
    if (preg_match_all('#https?://(?:www\.)?glowthdot\.com(/[^"\'\s>]*)?#i', $html, $m)) {
        $uniq = array_unique($m[0]);
        if ($uniq) {
            $report['external_glowth'][$slug] = array_slice($uniq, 0, 20);
        }
    }

    // links
    if (preg_match_all('#(?:href|action)=["\']([^"\']+)["\']#i', $html, $m)) {
        foreach ($m[1] as $href) {
            $target = slugFromHref($href);
            if ($target === null) {
                continue;
            }
            $report['link_targets'][$target] = ($report['link_targets'][$target] ?? 0) + 1;
            if (! isset($available[$target])) {
                $report['broken_links'][] = [
                    'from' => $slug,
                    'href' => $href,
                    'expected' => $target.'.html',
                ];
            }
        }
    }

    // assets
    if (preg_match_all('#(?:src|href)=["\']([^"\']+)["\']#i', $html, $m)) {
        foreach ($m[1] as $src) {
            $full = assetPath($src, $public);
            if ($full === null) {
                continue;
            }
            if (! is_file($full)) {
                $report['broken_assets'][] = [
                    'from' => $slug,
                    'src' => $src,
                ];
            }
        }
    }
}

// unique broken
$seen = [];
$uniqueBroken = [];
foreach ($report['broken_links'] as $b) {
    $k = $b['from'].'|'.$b['href'];
    if (isset($seen[$k])) {
        continue;
    }
    $seen[$k] = true;
    $uniqueBroken[] = $b;
}
$report['broken_links'] = $uniqueBroken;

$seenA = [];
$uniqueA = [];
foreach ($report['broken_assets'] as $b) {
    $k = $b['src'];
    if (isset($seenA[$k])) {
        continue;
    }
    $seenA[$k] = true;
    $uniqueA[] = $b;
}
$report['broken_assets'] = $uniqueA;

$out = $base.'/storage/app/live-seo/audit-report.json';
@mkdir(dirname($out), 0777, true);
file_put_contents($out, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "Pages: {$report['pages']}\n";
echo 'Broken link refs: '.count($report['broken_links'])."\n";
echo 'Unique broken assets: '.count($report['broken_assets'])."\n";
echo 'Pages with leftover glowthdot.com URLs: '.count($report['external_glowth'])."\n";
echo 'Pages missing SEO bits: '.count($report['missing_seo'])."\n";
echo "Report: $out\n";

if ($report['broken_links']) {
    echo "\n--- Broken links (first 40) ---\n";
    foreach (array_slice($report['broken_links'], 0, 40) as $b) {
        echo "{$b['from']} -> {$b['href']} (need {$b['expected']})\n";
    }
}
if ($report['broken_assets']) {
    echo "\n--- Broken assets (first 30) ---\n";
    foreach (array_slice($report['broken_assets'], 0, 30) as $b) {
        echo "{$b['from']} -> {$b['src']}\n";
    }
}
if ($report['missing_seo']) {
    echo "\n--- Missing SEO ---\n";
    foreach ($report['missing_seo'] as $s => $issues) {
        echo "$s: ".implode(', ', $issues)."\n";
    }
}
if ($report['external_glowth']) {
    echo "\n--- Pages with absolute glowthdot.com (count) ---\n";
    foreach ($report['external_glowth'] as $s => $urls) {
        echo "$s: ".count($urls)." sample urls\n";
    }
}
