<?php

/**
 * Builds storage/app/live-html/get-a-free-audit.html: the site's own header, footer and styles
 * (taken from the contact-us page) around the audit funnel in resources/audit-funnel/funnel.html.
 * Re-run after editing the funnel or when the header/footer change.
 * Usage: php scripts/build-audit-page.php
 */

$base = dirname(__DIR__);
$template = file_get_contents($base.'/storage/app/live-html/contact-us.html');
$funnel = file_get_contents($base.'/resources/audit-funnel/funnel.html');

$title = 'Free Salon & Clinic Growth Audit | Glowth';
$desc = 'Get a free growth audit for your UAE salon or clinic: Google rankings, Business Profile, website speed and competitors, sent on WhatsApp within 48 hours.';
$url = '/get-a-free-audit/';

function attr(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function swap(string $h, string $pattern, string $replacement, string $what): string
{
    $out = preg_replace($pattern, $replacement, $h, 1, $n);
    if ($out === null || $n !== 1) {
        exit("Template changed, could not replace: $what\n");
    }

    return $out;
}

$h = $template;

// Page content: everything inside .ast-container between the header and the footer
$h = swap(
    $h,
    '#(<div id="content" class="site-content">\s*<div class="ast-container">).*?(</div> <!-- ast-container -->)#s',
    '$1'."\n".str_replace('$', '\$', $funnel)."\n".'$2',
    'page content'
);

// Head: this page's own title, description and social tags
$h = swap($h, '#<title>.*?</title>#s', '<title>'.attr($title).'</title>', 'title');
$h = swap($h, '#<meta name="description" content="[^"]*"#', '<meta name="description" content="'.attr($desc).'"', 'description');
$h = swap($h, '#<link rel="canonical" href="[^"]*"#', '<link rel="canonical" href="'.$url.'"', 'canonical');
$h = swap($h, '#<meta property="og:type" content="[^"]*"#', '<meta property="og:type" content="website"', 'og:type');
$h = swap($h, '#<meta property="og:title" content="[^"]*"#', '<meta property="og:title" content="'.attr($title).'"', 'og:title');
$h = swap($h, '#<meta property="og:description" content="[^"]*"#', '<meta property="og:description" content="'.attr($desc).'"', 'og:description');
$h = swap($h, '#<meta property="og:url" content="[^"]*"#', '<meta property="og:url" content="'.$url.'"', 'og:url');

// Contact-page leftovers: its schema graph, article dates, reading time and page stylesheet
$h = preg_replace('#<script type="application/ld\+json" class="yoast-schema-graph">.*?</script>\s*#s', '', $h);
$h = preg_replace('#<meta (property="article:[^"]*"|name="twitter:(label|data)\d")[^>]*>\s*#', '', $h);
$h = preg_replace("#<link rel='stylesheet' id='elementor-post-336-css'[^>]*>\s*#", '', $h);
$h = preg_replace('#<link rel=[\'"](shortlink|alternate)[\'"][^>]*>\s*#', '', $h);
$h = str_replace(['page-id-336', 'elementor-page-336'], ['page-id-audit', 'elementor-page-audit'], $h);

file_put_contents($base.'/storage/app/live-html/get-a-free-audit.html', $h);
echo "storage/app/live-html/get-a-free-audit.html built (".round(strlen($h) / 1024)." KB)\n";
