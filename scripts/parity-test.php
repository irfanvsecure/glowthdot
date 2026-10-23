<?php

/**
 * Live vs local content parity + overall health check.
 */

$base = dirname(__DIR__);
$htmlDir = $base.'/storage/app/live-html';
$public = $base.'/public';
$localBase = 'http://127.0.0.1:8003';
$liveBase = 'https://glowthdot.com';

function fetch(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 GlowthParity/1.0',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$code, is_string($body) ? $body : ''];
}

function visibleText(string $html): string
{
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', ' ', $html) ?? $html;
    $html = preg_replace('#<style\b[^>]*>.*?</style>#is', ' ', $html) ?? $html;
    $html = preg_replace('#<!--.*?-->#s', ' ', $html) ?? $html;
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

    return trim($text);
}

function headings(string $html): array
{
    $out = [];
    if (preg_match_all('#<(h[1-3])[^>]*>(.*?)</\1>#is', $html, $m, PREG_SET_ORDER)) {
        foreach ($m as $row) {
            $t = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($row[2]))) ?? '');
            if ($t !== '' && strcasecmp($t, 'Home') !== 0) {
                $out[] = $t;
            }
        }
    }

    return $out;
}

$pages = [
    '/' => 'home',
    '/about-us/' => 'about-us',
    '/services/' => 'services',
    '/industries/' => 'industries',
    '/contact-us/' => 'contact-us',
    '/beauty-salons/' => 'beauty-salons',
    '/our-blog/' => 'our-blog',
];

echo "=== CONTENT PARITY (visible text / headings) ===\n";
foreach ($pages as $path => $slug) {
    [$lc, $lhtml] = fetch($liveBase.$path);
    [$jc, $jhtml] = fetch($localBase.$path);
    $lt = visibleText($lhtml);
    $jt = visibleText($jhtml);
    $lh = headings($lhtml);
    $jh = headings($jhtml);

    // Compare core phrase samples from live
    $samples = [];
    foreach ($lh as $h) {
        if (mb_strlen($h) > 12) {
            $samples[] = $h;
        }
        if (count($samples) >= 8) {
            break;
        }
    }
    $missingHeadings = [];
    foreach ($samples as $s) {
        $found = false;
        foreach ($jh as $x) {
            if (mb_stripos($x, mb_substr($s, 0, min(40, mb_strlen($s)))) !== false || similar_text($s, $x) / max(1, mb_strlen($s)) > 0.85) {
                $found = true;
                break;
            }
        }
        // also search full local text
        if (! $found && mb_stripos($jt, mb_substr($s, 0, min(50, mb_strlen($s)))) !== false) {
            $found = true;
        }
        if (! $found) {
            $missingHeadings[] = $s;
        }
    }

    $lenRatio = strlen($lt) > 0 ? round(strlen($jt) / strlen($lt), 3) : 0;
    echo "$path live=$lc local=$jc textRatio=$lenRatio headings live=".count($lh).' local='.count($jh);
    if ($missingHeadings) {
        echo ' MISSING_H='.count($missingHeadings);
        echo "\n  e.g. ".$missingHeadings[0];
    } else {
        echo ' headings_ok';
    }
    echo "\n";
}

echo "\n=== LOCAL PAGE HTTP CHECK ===\n";
$fail = 0;
$ok = 0;
foreach (glob($htmlDir.'/*.html') ?: [] as $file) {
    $slug = basename($file, '.html');
    if ($slug === 'home') {
        $path = '/';
    } elseif (str_starts_with($slug, 'category-')) {
        $rest = substr($slug, 9);
        if (preg_match('#^(clinic-marketing|salon-marketing)-page-(\d+)$#', $rest, $m)) {
            $path = '/category/'.$m[1].'/page/'.$m[2].'/';
        } else {
            $path = '/category/'.$rest.'/';
        }
    } elseif (str_starts_with($slug, 'author-')) {
        $path = '/author/'.substr($slug, 7).'/';
    } else {
        $path = '/'.$slug.'/';
    }
    [$code] = fetch($localBase.$path);
    if ($code === 200) {
        $ok++;
    } else {
        $fail++;
        echo "FAIL $path => $code\n";
    }
}
echo "pages_ok=$ok fail=$fail\n";

echo "\n=== REDIRECTS ===\n";
foreach (['/free-audit' => 301, '/get-a-free-audit' => 301, '/our-services' => 301, '/privacy-policy' => 301] as $p => $expect) {
    $ch = curl_init($localBase.$p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 15]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "$p => $code ".(in_array($code, [301, 302], true) ? 'OK' : 'UNEXPECTED')."\n";
}

echo "\n=== SEO FILES ===\n";
foreach (['/robots.txt', '/sitemap_index.xml', '/page-sitemap.xml', '/post-sitemap.xml'] as $p) {
    [$code] = fetch($localBase.$p);
    echo "$p => $code\n";
}

echo "\n=== STYLE LAYER FLAGS ===\n";
$home = file_get_contents($htmlDir.'/home.html') ?: '';
echo 'home-enhance.css: '.(str_contains($home, 'home-enhance.css') ? 'PRESENT' : 'absent')."\n";
echo 'home-enhance.js: '.(str_contains($home, 'home-enhance.js') ? 'PRESENT' : 'absent')."\n";
echo 'glowth-hero-fallback: '.(str_contains($home, 'glowth-hero-fallback') ? 'PRESENT' : 'absent')."\n";
echo 'glowth-card-link: '.(str_contains($home, 'glowth-card-link') ? 'PRESENT' : 'absent')."\n";

echo "\n=== SAMPLE TEXT CHECK (home) ===\n";
[$lc, $lhtml] = fetch($liveBase.'/');
[$jc, $jhtml] = fetch($localBase.'/');
$phrases = [
    'Beauty Salon & Clinic Marketing Agency',
    'Get My SEO Audit',
    'How It Works',
    'Google Ads Certified',
    'Beauty industries we specialize',
    'Free Salon & Clinic Growth Audit',
    'Glowth',
];
$lt = visibleText($lhtml);
$jt = visibleText($jhtml);
foreach ($phrases as $p) {
    $inLive = mb_stripos($lt, $p) !== false;
    $inLocal = mb_stripos($jt, $p) !== false;
    echo ($inLive === $inLocal && $inLocal ? 'OK' : 'DIFF')." \"$p\" live=".($inLive ? 'y' : 'n').' local='.($inLocal ? 'y' : 'n')."\n";
}
