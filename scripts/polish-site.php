<?php

/**
 * Polish mirror: fetch missing archives, strip WP dead ends,
 * add missing meta descriptions, absolute sitemaps + robots.
 */

$base = dirname(__DIR__);
$htmlDir = $base.'/storage/app/live-html';
$public = $base.'/public';
$domain = 'https://glowthdot.com';

function fetchUrl(string $url): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'GlowthMirror/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 200 && $code < 300 && is_string($body) && strlen($body) > 500) {
        return $body;
    }

    return null;
}

function localizeHtml(string $html): string
{
    $replacements = [
        'https://glowthdot.com/wp-content/uploads/' => '/images/',
        'http://glowthdot.com/wp-content/uploads/' => '/images/',
        'https://glowthdot.com/wp-content/themes/' => '/assets/themes/',
        'http://glowthdot.com/wp-content/themes/' => '/assets/themes/',
        'https://glowthdot.com/wp-content/plugins/' => '/assets/plugins/',
        'http://glowthdot.com/wp-content/plugins/' => '/assets/plugins/',
        'https://glowthdot.com/wp-includes/' => '/assets/includes/',
        'http://glowthdot.com/wp-includes/' => '/assets/includes/',
        'https://www.glowthdot.com/' => '/',
        'http://www.glowthdot.com/' => '/',
        'https://glowthdot.com/' => '/',
        'http://glowthdot.com/' => '/',
        '//glowthdot.com/' => '/',
        '//www.glowthdot.com/' => '/',
    ];
    $html = str_replace(array_keys($replacements), array_values($replacements), $html);

    return $html;
}

function stripWpPlumbing(string $html): string
{
    // Remove link tags to feeds / xmlrpc / oEmbed / RSD
    $html = preg_replace('#<link[^>]+(?:alternate|EditURI|wlwmanifest|shortlink|https://api\.w\.org/)[^>]*>\s*#i', '', $html) ?? $html;
    $html = preg_replace('#<link[^>]+href=["\'][^"\']*(?:/feed/|/comments/feed|/xmlrpc\.php)[^"\']*["\'][^>]*>\s*#i', '', $html) ?? $html;

    // Rewrite author archive → our-blog
    $html = preg_replace('#href=(["\'])/author/[^"\']*(["\'])#i', 'href=$1/our-blog/$2', $html) ?? $html;

    // Dead WP form endpoints → contact
    $html = preg_replace('#action=(["\'])/wp-comments-post\.php(["\'])#i', 'action=$1/contact-us/$2', $html) ?? $html;
    $html = preg_replace('#href=(["\'])/xmlrpc\.php[^"\']*(["\'])#i', 'href=$1#$2', $html) ?? $html;
    $html = preg_replace('#href=(["\'])/comments/feed/?([^"\']*)(["\'])#i', 'href=$1#$3', $html) ?? $html;
    $html = preg_replace('#href=(["\'])/[^"\']+/feed/?([^"\']*)(["\'])#i', 'href=$1#$3', $html) ?? $html;

    // Search form: keep as GET to home (static, no WP search)
    $html = preg_replace(
        '#<form[^>]*role=["\']search["\'][^>]*>.*?</form>#is',
        '<form role="search" method="get" action="/our-blog/"><label class="screen-reader-text" for="s">Search</label><input type="search" id="s" name="s" placeholder="Search…" /><button type="submit">Search</button></form>',
        $html
    ) ?? $html;

    return $html;
}

function ensureMetaDescription(string $html, string $fallback): string
{
    if (preg_match('#name=["\']description["\']#i', $html)) {
        return $html;
    }
    $desc = $fallback;
    if (preg_match('#property=["\']og:description["\']\s+content=["\']([^"\']+)["\']#i', $html, $m)) {
        $desc = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    } elseif (preg_match('#<title>([^<]+)</title>#i', $html, $m)) {
        $desc = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')).' — Glowth, beauty salon & clinic marketing agency in Dubai, UAE.';
    }
    $desc = htmlspecialchars(mb_substr($desc, 0, 160), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $tag = '<meta name="description" content="'.$desc.'" />'."\n";
    if (preg_match('#</title>#i', $html)) {
        return preg_replace('#</title>#i', "</title>\n\t".$tag, $html, 1) ?? $html;
    }

    return $html;
}

// --- Fetch missing archives ---
$toFetch = [
    'category-clinic-marketing-page-2' => $domain.'/category/clinic-marketing/page/2/',
    'category-salon-marketing-page-2' => $domain.'/category/salon-marketing/page/2/',
    'author-abu-bakar' => $domain.'/author/abu-bakar/',
];

foreach ($toFetch as $slug => $url) {
    echo "Fetching $url ... ";
    $body = fetchUrl($url);
    if (! $body) {
        echo "FAIL\n";
        continue;
    }
    $body = localizeHtml($body);
    file_put_contents($htmlDir.'/'.$slug.'.html', $body);
    echo 'OK ('.strlen($body)." bytes)\n";
}

// Redirects for author (also in routes) — keep file for deep links
$redirectMap = [
    'free-audit' => 'contact-us',
    'get-a-free-audit' => 'contact-us',
];

// --- Polish every HTML file ---
$fallbacks = [
    'our-blog' => 'Glowth blog: salon and clinic marketing tips for Dubai, Abu Dhabi, Sharjah and the UAE.',
    'privacy-policy-2' => 'Privacy policy for Glowth digital marketing agency — how we collect and use your data.',
    'terms-of-service' => 'Terms of service for Glowth digital marketing services in the UAE.',
    'category-clinic-marketing' => 'Clinic marketing articles and guides for aesthetic clinics in Dubai and the UAE — by Glowth.',
    'category-salon-marketing' => 'Salon marketing articles and guides for beauty salons in Dubai and the UAE — by Glowth.',
    'category-clinic-marketing-page-2' => 'More clinic marketing articles for aesthetic clinics in Dubai and the UAE — by Glowth.',
    'category-salon-marketing-page-2' => 'More salon marketing articles for beauty salons in Dubai and the UAE — by Glowth.',
    'author-abu-bakar' => 'Articles by Abu Bakar at Glowth — salon and clinic marketing insights for the UAE.',
];

$count = 0;
foreach (glob($htmlDir.'/*.html') ?: [] as $file) {
    $slug = basename($file, '.html');
    $html = file_get_contents($file) ?: '';
    $html = localizeHtml($html);
    $html = stripWpPlumbing($html);
    $html = ensureMetaDescription($html, $fallbacks[$slug] ?? 'Glowth — digital marketing for salons and clinics in Dubai, UAE.');
    file_put_contents($file, $html);
    $count++;
}
echo "Polished $count HTML files\n";

// --- Sitemaps (absolute URLs) ---
$blogSlugs = [];
$postSitemapLive = $base.'/storage/app/live-seo/post-sitemap.xml';
if (is_file($postSitemapLive)) {
    if (preg_match_all('#<loc>https?://[^/]+/([^<]+)/</loc>#', file_get_contents($postSitemapLive), $m)) {
        $blogSlugs = $m[1];
    }
}

$pages = [];
$posts = [];
foreach (glob($htmlDir.'/*.html') ?: [] as $file) {
    $slug = basename($file, '.html');
    // skip pagination/author from main page sitemap? include author as page; pagination in category
    if (str_ends_with($slug, '-page-2') || str_ends_with($slug, '-page-3')) {
        continue;
    }
    if ($slug === 'author-abu-bakar') {
        continue; // noindex-ish; keep serving but not in sitemap (WP often noindexes authors)
    }
    if ($slug === 'home') {
        $pages[] = ['loc' => $domain.'/', 'file' => $file];
        continue;
    }
    // map category-* back to /category/.../
    if (str_starts_with($slug, 'category-')) {
        $path = '/category/'.substr($slug, 9).'/';
        $pages[] = ['loc' => $domain.$path, 'file' => $file];
        continue;
    }
    $item = ['loc' => $domain.'/'.$slug.'/', 'file' => $file];
    if (in_array($slug, $blogSlugs, true)) {
        $posts[] = $item;
    } else {
        $pages[] = $item;
    }
}

function lastmod(string $file): string
{
    return gmdate('c', filemtime($file) ?: time());
}

function writeUrlset(string $path, array $items): void
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
    foreach ($items as $item) {
        $xml .= "\t<url>\n";
        $xml .= "\t\t<loc>".htmlspecialchars($item['loc'], ENT_XML1)."</loc>\n";
        $xml .= "\t\t<lastmod>".lastmod($item['file'])."</lastmod>\n";
        $xml .= "\t</url>\n";
    }
    $xml .= '</urlset>'."\n";
    file_put_contents($path, $xml);
}

writeUrlset($public.'/page-sitemap.xml', $pages);
writeUrlset($public.'/post-sitemap.xml', $posts);

$cats = [];
foreach (['clinic-marketing', 'salon-marketing'] as $c) {
    $f = $htmlDir.'/category-'.$c.'.html';
    if (is_file($f)) {
        $cats[] = ['loc' => $domain.'/category/'.$c.'/', 'file' => $f];
    }
}
writeUrlset($public.'/category-sitemap.xml', $cats);

$now = gmdate('c');
$index = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
$index .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
foreach (['post-sitemap.xml', 'page-sitemap.xml', 'category-sitemap.xml'] as $name) {
    $index .= "\t<sitemap>\n";
    $index .= "\t\t<loc>".$domain.'/'.$name."</loc>\n";
    $index .= "\t\t<lastmod>{$now}</lastmod>\n";
    $index .= "\t</sitemap>\n";
}
$index .= '</sitemapindex>'."\n";
file_put_contents($public.'/sitemap_index.xml', $index);
file_put_contents($public.'/sitemap.xml', $index);

file_put_contents($public.'/robots.txt', "User-agent: *\nDisallow:\n\nSitemap: {$domain}/sitemap_index.xml\n");

echo 'Sitemap pages: '.count($pages).', posts: '.count($posts).', cats: '.count($cats)."\n";
echo "Done.\n";
