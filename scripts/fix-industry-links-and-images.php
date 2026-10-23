<?php

/**
 * 1) Wrap industry icon-box titles with links to their pages (industries + home).
 * 2) Scan all live-html + elementor CSS for missing images and download from live.
 */

$base = dirname(__DIR__);
$htmlDir = $base.'/storage/app/live-html';
$public = $base.'/public';
$liveOrigin = 'https://glowthdot.com';

$titleToSlug = [
    'Arabic Beauty Salon' => 'arabic-beauty-salon',
    'Russian Beauty Salon' => 'russian-beauty-salon',
    'Korean Beauty Salon' => 'korean-beauty-salon',
    'Hair Salons' => 'hair-salons',
    'Ladies Salons' => 'ladies-salon-marketing',
    'Ladies Salon' => 'ladies-salon-marketing',
    'Barber Shops' => 'barber-shops',
    'Nail Salons' => 'nail-salons',
    'Day Spas' => 'day-spas',
    'Medical Spas' => 'medical-spas',
    'Aesthetic Clinics' => 'aesthetic-clinics',
    'Dermatology Clinics' => 'dermatology-clinics',
    'Cosmetic Clinics' => 'aesthetic-clinics', // no dedicated page
    'Plastic Surgery Clinics' => 'plastic-surgery-clinics',
    'Wellness Centers' => 'wellness-centers',
    'Lash Studios' => 'lash-studios',
    'Brow Studios' => 'brow-studios',
    'Makeup Studios' => 'makeup-studios',
    'Hair Extension Studios' => 'hair-extension-studios',
    'Laser Hair Removal Clinics' => 'laser-hair-removal-clinics',
    'Skin Care Clinics' => 'skin-care-clinics',
    'Massage Centers' => 'massage-centers',
    'Hammam Center' => 'hammam-center',
    'Moroccan Bath Centre' => 'moroccan-bath-centre',
    'Cosmetic & Makeup Beauty Brand' => 'cosmetic-makeup-beauty-brand',
    'Skincare Beauty Brand' => 'skincare-beauty-brand',
    'Hair Care Beauty Brand' => 'hair-care-beauty-brand',
    'Beauty Salons' => 'beauty-salons',
    'Beauty Academies' => 'beauty-academies',
    // services on home
    'AI Appointment Booking Bot' => 'ai-appointment-booking-bot',
    'Websites Development' => 'websites-landing-pages-for-beauty',
    'Landing Pages' => 'websites-landing-pages-for-beauty',
    'Search Engine Optimization' => 'seo-for-beauty-salons-clinics',
    'Google Ads' => 'google-ads-for-beauty',
    'Meta Ads (FB & IG)' => 'instagram-facebook-ads-for-beauty',
    'Social Media Management' => 'social-media-management-for-beauty',
    'Ecommerce Development' => 'ecommerce-for-beauty',
    'Reviews & Reputation' => 'reviews-reputation',
    'Dubai' => 'dubai',
    'Abu Dhabi' => 'abu-dhabi',
    'Sharjah' => 'sharjah',
];

function linkIconBoxes(string $html, array $titleToSlug, string $htmlDir): array
{
    $linked = 0;
    $skipped = 0;

    foreach ($titleToSlug as $title => $slug) {
        $file = $htmlDir.'/'.$slug.'.html';
        if (! is_file($file)) {
            $skipped++;
            continue;
        }
        $href = '/'.$slug.'/';

        // Pattern: title inside icon-box without an existing <a>
        // <p class="elementor-icon-box-title"> <span> TITLE </span> </p>
        // or with nested spans
        $quoted = preg_quote($title, '/');
        $patterns = [
            // span with title, no surrounding a yet
            '/(<p class="elementor-icon-box-title">\s*)(?:<a\b[^>]*>\s*)?(<span\b[^>]*>\s*)'.$quoted.'(\s*<\/span>)(?:\s*<\/a>)?(\s*<\/p>)/i',
            '/(<p class="elementor-icon-box-title">\s*)'.$quoted.'(\s*<\/p>)/i',
        ];

        $html2 = preg_replace_callback(
            $patterns[0],
            function ($m) use ($href, &$linked) {
                // already linked?
                if (stripos($m[0], '<a ') !== false) {
                    return $m[0];
                }
                $linked++;

                return $m[1].'<a href="'.$href.'">'.$m[2].$GLOBALS['_title_tmp'].$m[3].'</a>'.$m[4];
            },
            $html,
            -1,
            $count
        );

        // Fix: use title in replacement properly
        $html2 = preg_replace_callback(
            $patterns[0],
            function ($m) use ($href, $title, &$linked) {
                if (stripos($m[0], '<a ') !== false || stripos($m[0], 'href=') !== false) {
                    return $m[0];
                }
                $linked++;

                return $m[1].'<a href="'.$href.'">'.$m[2].$title.$m[3].'</a>'.$m[4];
            },
            $html
        );

        if ($html2 !== null && $html2 !== $html) {
            $html = $html2;
        } else {
            // try plain title without span
            $html2 = preg_replace_callback(
                $patterns[1],
                function ($m) use ($href, $title, &$linked) {
                    if (stripos($m[0], '<a ') !== false) {
                        return $m[0];
                    }
                    $linked++;

                    return $m[1].'<a href="'.$href.'">'.$title.'</a>'.$m[2];
                },
                $html
            );
            if ($html2 !== null) {
                $html = $html2;
            }
        }

        // Also wrap entire icon-box container if Elementor uses link-none — make the icon clickable too
        // Wrap elementor-icon-box-wrapper content: add link around icon+title by converting title link only is enough for UX
    }

    return [$html, $linked, $skipped];
}

/**
 * Better single-pass linker: find each icon-box-title and wrap if unlinked.
 */
function linkIconBoxesV2(string $html, array $titleToSlug, string $htmlDir): array
{
    $linked = 0;
    $html = preg_replace_callback(
        '/(<p class="elementor-icon-box-title">)(.*?)(<\/p>)/is',
        function ($m) use ($titleToSlug, $htmlDir, &$linked) {
            $inner = $m[2];
            if (stripos($inner, '<a ') !== false) {
                return $m[0]; // already linked
            }
            $text = trim(html_entity_decode(strip_tags($inner)));
            // normalize whitespace
            $text = preg_replace('/\s+/', ' ', $text) ?? $text;
            if (! isset($titleToSlug[$text])) {
                return $m[0];
            }
            $slug = $titleToSlug[$text];
            if (! is_file($htmlDir.'/'.$slug.'.html')) {
                return $m[0];
            }
            $href = '/'.$slug.'/';
            $linked++;
            // keep original inner markup, wrap with <a>
            return $m[1].'<a href="'.$href.'">'.$inner.'</a>'.$m[3];
        },
        $html
    ) ?? $html;

    return [$html, $linked];
}

function localizeUrl(string $url): string
{
    $url = preg_replace('#^https?://(www\.)?glowthdot\.com#i', '', $url) ?? $url;
    $url = str_replace('/wp-content/uploads/', '/images/', $url);
    $url = str_replace('/wp-content/themes/', '/assets/themes/', $url);
    $url = str_replace('/wp-content/plugins/', '/assets/plugins/', $url);
    $url = str_replace('/wp-includes/', '/assets/includes/', $url);

    return $url;
}

function collectAssetPaths(string $content): array
{
    $paths = [];
    if (preg_match_all('/(?:src|href|data-src|data-lazy-src|data-bg|content)=["\']([^"\']+)["\']/i', $content, $m)) {
        foreach ($m[1] as $u) {
            $u = localizeUrl($u);
            $path = parse_url($u, PHP_URL_PATH);
            if (! is_string($path) || $path === '') {
                continue;
            }
            if (preg_match('#^/(images|assets)/#', $path)) {
                $paths[$path] = true;
            }
        }
    }
    if (preg_match_all('/url\((["\']?)([^)\'"]+)\1\)/i', $content, $m)) {
        foreach ($m[2] as $u) {
            if (str_starts_with($u, 'data:')) {
                continue;
            }
            $u = localizeUrl($u);
            $path = parse_url($u, PHP_URL_PATH);
            if (is_string($path) && preg_match('#^/(images|assets)/#', $path)) {
                $paths[$path] = true;
            }
        }
    }
    // srcset
    if (preg_match_all('/srcset=["\']([^"\']+)["\']/i', $content, $m)) {
        foreach ($m[1] as $set) {
            foreach (preg_split('/\s*,\s*/', $set) ?: [] as $part) {
                $url = trim(explode(' ', trim($part))[0]);
                $url = localizeUrl($url);
                $path = parse_url($url, PHP_URL_PATH);
                if (is_string($path) && preg_match('#^/(images|assets)/#', $path)) {
                    $paths[$path] = true;
                }
            }
        }
    }

    return array_keys($paths);
}

function downloadAsset(string $localPath, string $public, string $liveOrigin): bool
{
    $full = $public.$localPath;
    if (is_file($full) && filesize($full) > 0) {
        return true;
    }
    // map local path back to live URL
    $livePath = $localPath;
    $livePath = preg_replace('#^/images/#', '/wp-content/uploads/', $livePath) ?? $livePath;
    $livePath = preg_replace('#^/assets/themes/#', '/wp-content/themes/', $livePath) ?? $livePath;
    $livePath = preg_replace('#^/assets/plugins/#', '/wp-content/plugins/', $livePath) ?? $livePath;
    $livePath = preg_replace('#^/assets/includes/#', '/wp-includes/', $livePath) ?? $livePath;
    $url = $liveOrigin.$livePath;

    $dir = dirname($full);
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'GlowthMirror/1.0',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code >= 200 && $code < 300 && is_string($body) && strlen($body) > 0) {
        file_put_contents($full, $body);

        return true;
    }

    return false;
}

// --- Link industry cards on key pages ---
$targetPages = ['industries', 'home', 'beauty-salons', 'aesthetic-clinics', 'services', 'dubai', 'abu-dhabi', 'sharjah'];
$totalLinked = 0;
foreach ($targetPages as $slug) {
    $file = $htmlDir.'/'.$slug.'.html';
    if (! is_file($file)) {
        continue;
    }
    $html = file_get_contents($file) ?: '';
    [$html, $n] = linkIconBoxesV2($html, $titleToSlug, $htmlDir);
    file_put_contents($file, $html);
    echo "Linked $n icon-boxes on $slug\n";
    $totalLinked += $n;
}
echo "Total new links: $totalLinked\n";

// --- Also link ALL pages' icon boxes (footer/header shared? usually per-page) ---
foreach (glob($htmlDir.'/*.html') ?: [] as $file) {
    $slug = basename($file, '.html');
    if (in_array($slug, $targetPages, true)) {
        continue;
    }
    $html = file_get_contents($file) ?: '';
    [$html, $n] = linkIconBoxesV2($html, $titleToSlug, $htmlDir);
    if ($n > 0) {
        file_put_contents($file, $html);
        echo "Linked $n on $slug\n";
        $totalLinked += $n;
    }
}

// --- Collect + download missing assets ---
$needed = [];
foreach (glob($htmlDir.'/*.html') ?: [] as $file) {
    foreach (collectAssetPaths(file_get_contents($file) ?: '') as $p) {
        $needed[$p] = true;
    }
}
foreach (glob($public.'/images/elementor/css/*.css') ?: [] as $css) {
    foreach (collectAssetPaths(file_get_contents($css) ?: '') as $p) {
        $needed[$p] = true;
    }
}

$missing = [];
foreach (array_keys($needed) as $path) {
    if (! is_file($public.$path) || filesize($public.$path) === 0) {
        $missing[] = $path;
    }
}
echo 'Assets referenced: '.count($needed).', missing: '.count($missing)."\n";

$ok = 0;
$fail = 0;
foreach ($missing as $path) {
    if (downloadAsset($path, $public, $liveOrigin)) {
        echo "DL OK $path\n";
        $ok++;
    } else {
        echo "DL FAIL $path\n";
        $fail++;
    }
}
echo "Downloaded $ok, failed $fail\n";

// Verify icon links on industries
$ind = file_get_contents($htmlDir.'/industries.html') ?: '';
preg_match_all('/elementor-icon-box-title.*?<a href="([^"]+)">/is', $ind, $m);
echo 'Industries icon-box links now: '.count($m[1])."\n";
foreach (array_unique($m[1]) as $h) {
    echo "  $h\n";
}
