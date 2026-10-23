<?php

/**
 * SEO + speed fixes for the mirrored pages in storage/app/live-html.
 * Safe to re-run: every step checks before it changes anything.
 * A copy of the untouched pages is kept in storage/app/live-html-backup-<date> on first run.
 * Usage: php scripts/fix-seo-speed.php
 */

$base = dirname(__DIR__);
$htmlDir = $base.'/storage/app/live-html';
$public = $base.'/public';

$backup = $base.'/storage/app/live-html-backup-'.date('Ymd');
if (! is_dir($backup)) {
    mkdir($backup, 0777, true);
    foreach (glob($htmlDir.'/*.html') as $f) {
        copy($f, $backup.'/'.basename($f));
    }
    echo "Backup: $backup\n";
}

// Titles over 60 characters (Google cuts them off)
$titles = [
    'home' => 'Digital Marketing Agency for Salons & Clinics UAE | Glowth',
    'aesthetic-clinics' => 'Aesthetic Clinic Marketing Agency in Dubai | Glowth',
    'clinic-google-maps-ranking' => 'Clinic Google Maps SEO: Why You\'re Not Showing Up | Glowth',
    'clinic-instagram-ads-cost-dubai' => 'Clinic Instagram Ads Cost Per Consultation in Dubai | Glowth',
    'clinic-marketing-uae-guide' => 'Aesthetic Clinic Marketing in UAE: 2026 Patient Growth Guide',
    'clinic-packages-membership-pricing-dha' => 'Clinic Packages & Membership Pricing: DHA-Safe Guide',
    'clinic-patient-retention-dha' => 'Aesthetic Clinic Patient Retention: DHA-Safe Guide 2026',
    'salon-client-retention-loyalty-dubai' => 'Salon Client Retention Dubai: Loyalty & Win-Back Guide',
    'salon-ecommerce-beauty-products' => 'Salon E-Commerce: Should You Sell Products Online? | Glowth',
    'salon-marketing-dubai-guide' => 'Salon Marketing in Dubai: Get More Bookings (2026 Guide)',
    'salon-no-shows-cancellations-dubai' => 'Reduce Salon No-Shows in Dubai: Deposits & Reminders',
    'salon-website-conversion-fixes' => 'Salon Website Conversion: 7 Fixes for More Bookings',
    'salon-whatsapp-booking-leak' => 'Salon WhatsApp Leak: Why Bookings Vanish After 7pm',
];

// Meta descriptions over 160 characters
$descriptions = [
    'beauty-marketing-cost-dubai' => 'Real monthly costs for SEO, ads, websites, social and reviews for Dubai salons and clinics, plus an honest DIY-versus-agency comparison.',
    'beauty-salons' => 'Beauty salon marketing in the UAE: local SEO, Google & Meta ads, booking websites and reviews for hair, unisex, men\'s and parlour salons.',
    'clinic-bad-review-response-dha' => 'DHA-safe reply templates for bad reviews at UAE clinics that never confirm treatment details, plus how to grow reviews without incentives.',
    'clinic-google-ads-keywords' => 'The four keyword families every UAE clinic should bid on, the negative keywords that stop budget leaks, and the match types that drive bookings.',
    'clinic-google-maps-ranking' => 'Why your clinic isn\'t showing on Google Maps, what actually moves map-pack rankings, and the DHA-safe way to optimise your Business Profile.',
    'clinic-instagram-ads-cost-dubai' => 'The real cost-per-consultation math for clinic Instagram ads in Dubai, the number most clinics forget to track, and DHA-safe creative that converts.',
    'clinic-marketing-uae-guide' => 'How UAE aesthetic clinics grow within healthcare ad rules: treatment SEO, compliant ads, reviews and instant enquiry response.',
    'clinic-social-media-content-ideas-dha' => 'Six DHA-safe content buckets that build patient trust without before-and-after photos, plus a batching system for six weeks of posts.',
    'clinic-website-conversion-dha' => 'Five conversion fixes so patients can find prices and book on your clinic website in two taps, and why hiding pricing isn\'t the DHA-safe choice.',
    'clinic-whatsapp-booking-bot-dha' => 'How to build a DHA-safe WhatsApp booking bot that answers after-hours patient enquiries without diagnosing or guaranteeing outcomes.',
    'contact-us' => 'Talk to a beauty marketing specialist about SEO, Google & Meta ads and AI booking for your salon or clinic in Dubai, Abu Dhabi & Sharjah.',
    'salon-bad-review-response-templates' => 'Word-for-word reply templates for the five bad reviews every salon gets, including fake reviews and rude-staff claims. Protect your Maps ranking.',
    'salon-content-ideas-30-days' => '30 days of salon content ideas in six repeatable buckets, plus a weekly posting pattern and batching method that fits a busy salon schedule.',
    'salon-ecommerce-beauty-products' => 'The real e-commerce math for Dubai salons: which products sell online, how to set up reorders, and the UAE checkout details that win sales.',
    'salon-instagram-ads-cost-dubai' => 'The honest math of Instagram ads for Dubai salons: cost per booking, why cheap campaigns cost more, and the levers that cut cost per client.',
    'salon-marketing-dubai-guide' => 'The 7-step marketing system Dubai salons use to stay fully booked: Google Maps SEO, Instagram ads, reviews and instant booking replies.',
    'salon-membership-subscription-pricing-dubai' => 'Is a membership right for your salon? Pricing math, service fit and pause or cancellation policies Dubai salons need before launching one.',
    'salon-not-showing-google-maps' => 'Five reasons salons vanish from Google\'s "near me" results, and the priority-order fixes to reach the top-3 map pack. Free visibility audit.',
    'salon-website-conversion-fixes' => '7 conversion fixes for Dubai salon websites: booking buttons up top, visible prices, WhatsApp contact, service pages, speed and proof.',
    'salon-whatsapp-booking-leak' => 'Salon enquiries that message after 7pm and quietly book elsewhere cost more than you think. How the WhatsApp leak works and how to plug it.',
];

// Images that shipped with an empty alt
$alts = [
    '/images/2026/07/photo-1570172619644-dfd03ed5d881.jpg' => 'Aesthetician applying a facial mask to a client at a beauty clinic',
    '/images/2026/07/photo-1560750588-73207b1ef5b8.jpg' => 'Calm day spa lounge with a plunge pool and tropical plants',
    '/images/2026/07/photo-1522337660859-02fbefca4702.jpg' => 'Client painting her nails with pink nail polish',
    '/images/2026/09/salon-google-ads-keywords-cover-1.png' => 'Google Ads keywords for beauty salons in Dubai',
];

// Mojibake left over from a double UTF-8 encode
$mojibake = [
    'Ã—' => '×', 'â€”' => '—', 'â€“' => '–', 'â€™' => '’', 'â€˜' => '‘',
    'â€œ' => '“', 'â€¦' => '…', 'â€¢' => '•', 'Â·' => '·', 'Â ' => ' ', "Â\u{00A0}" => "\u{00A0}",
];

foreach ([$titles, $descriptions] as $i => $set) {
    foreach ($set as $slug => $text) {
        $max = $i === 0 ? 60 : 160;
        if (mb_strlen($text) > $max) {
            exit("Too long ($max): $slug ".mb_strlen($text)."\n");
        }
    }
}

function attr(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
}

function setMeta(string $h, string $attr, string $key, string $value): string
{
    $re = '#(<meta\s+'.$attr.'=["\']'.preg_quote($key, '#').'["\']\s+content=)(["\'])(.*?)\2#is';

    return preg_replace_callback($re, fn ($m) => $m[1].'"'.attr($value).'"', $h, 1) ?? $h;
}

/** Update name/description of the WebPage node inside the Yoast JSON-LD graph. */
function setSchema(string $h, ?string $title, ?string $desc): string
{
    return preg_replace_callback(
        '#(<script type="application/ld\+json" class="yoast-schema-graph">)(.*?)(</script>)#s',
        function ($m) use ($title, $desc) {
            $data = json_decode($m[2], true);
            if (! is_array($data) || ! isset($data['@graph'])) {
                return $m[0];
            }
            foreach ($data['@graph'] as &$node) {
                $types = (array) ($node['@type'] ?? []);
                if (array_intersect($types, ['WebPage', 'CollectionPage', 'AboutPage', 'ContactPage', 'FAQPage', 'ProfilePage'])) {
                    if ($title !== null) {
                        $node['name'] = $title;
                    }
                    if ($desc !== null && isset($node['description'])) {
                        $node['description'] = $desc;
                    }
                }
            }
            unset($node);

            return $m[1].json_encode($data, JSON_UNESCAPED_UNICODE).$m[3];
        },
        $h,
        1
    ) ?? $h;
}

function localImage(string $public, string $src): ?string
{
    $path = parse_url(html_entity_decode($src), PHP_URL_PATH);
    if (! $path || ! str_starts_with($path, '/images/')) {
        return null;
    }
    $file = $public.$path;

    return is_file($file) ? $file : null;
}

$log = [];
foreach (glob($htmlDir.'/*.html') as $file) {
    $slug = basename($file, '.html');
    $h = $orig = file_get_contents($file);
    $did = [];

    // 0. Uploads still pointing at the old WordPress host
    $before = $h;
    $h = str_replace(
        ['https://glowthdot.com/wp-content/uploads/', 'https:\/\/glowthdot.com\/wp-content\/uploads\/'],
        ['/images/', '\/images\/'],
        $h
    );
    if ($h !== $before) {
        $did[] = 'wp-uploads-urls';
    }

    // 0b. Every "free audit" link lands on the audit funnel: the top banner pointed at
    // #audit (only on the home page), the header button at /contact-us/, others at /free-audit/
    if ($slug !== 'get-a-free-audit') {
        $before = $h;
        $h = preg_replace('#(<a href=")\#audit(">[^<]*Claim yours)#', '$1/get-a-free-audit/$2', $h);
        $h = preg_replace('#(<a class="ast-custom-button-link" href=")[^"]*("[^>]*aria-label="Free Growth Audit")#', '$1/get-a-free-audit/$2', $h);
        $h = str_replace(['href="/free-audit/"', 'href="#free-audit"'], 'href="/get-a-free-audit/"', $h);
        if ($h !== $before) {
            $did[] = 'audit-links';
        }
    }

    // 0c. Industry cards: the icon's closing </span> was written as </a>, which ended the
    // card link right after the icon, so the title and description were not clickable
    $before = $h;
    $h = preg_replace(
        '#<span href="[^"]*" class="elementor-icon" tabindex="-1" aria-label="[^"]*">(\s*<svg.*?</svg>\s*)</a>#s',
        '<span class="elementor-icon">$1</span>',
        $h
    );
    if ($h !== $before) {
        $did[] = 'card-links';
    }

    // 1. Titles / descriptions
    if (isset($titles[$slug])) {
        $t = $titles[$slug];
        $h = preg_replace_callback('#<title>.*?</title>#s', fn () => '<title>'.attr($t).'</title>', $h, 1);
        $h = setMeta($h, 'property', 'og:title', $t);
        $h = setMeta($h, 'name', 'twitter:title', $t);
        $did[] = 'title';
    }
    if (isset($descriptions[$slug])) {
        $d = $descriptions[$slug];
        $h = setMeta($h, 'name', 'description', $d);
        $h = setMeta($h, 'property', 'og:description', $d);
        $h = setMeta($h, 'name', 'twitter:description', $d);
        $did[] = 'description';
    }
    if (isset($titles[$slug]) || isset($descriptions[$slug])) {
        $h = setSchema($h, $titles[$slug] ?? null, $descriptions[$slug] ?? null);
    }

    // 2. Mojibake
    $before = $h;
    $h = strtr($h, $mojibake);
    if ($h !== $before) {
        $did[] = 'encoding';
    }

    // 3. Images: alt, width/height, lazy loading
    $bodyPos = stripos($h, '<body');
    $contentPos = stripos($h, 'id="content"');
    $contentPos = $contentPos === false ? $bodyPos : $contentPos;
    $hasPriority = (bool) preg_match('#<img\b[^>]*fetchpriority=["\']high#i', $h);
    $firstContentDone = false;
    $imgFixes = ['alt' => 0, 'dims' => 0, 'lazy' => 0];

    $h = preg_replace_callback('#<img\b[^>]*>#i', function ($m) use (&$firstContentDone, &$imgFixes, $hasPriority, $contentPos, $alts, $public) {
        [$tag, $pos] = $m[0];
        preg_match('#\bsrc=["\']([^"\']+)#i', $tag, $s);
        $src = $s[1] ?? '';
        $path = parse_url(html_entity_decode($src), PHP_URL_PATH) ?: '';

        if (isset($alts[$path]) && preg_match('#\balt=(["\'])\1#i', $tag)) {
            $tag = preg_replace('#\balt=(["\'])\1#i', 'alt="'.attr($alts[$path]).'"', $tag, 1);
            $imgFixes['alt']++;
        }

        if (! preg_match('#\bwidth=#i', $tag) && ! preg_match('#\bheight=#i', $tag) && ($f = localImage($public, $src))) {
            $size = @getimagesize($f);
            if ($size) {
                $tag = preg_replace('#^<img\b#i', '<img width="'.$size[0].'" height="'.$size[1].'"', $tag);
                $imgFixes['dims']++;
            }
        }

        $inContent = $pos > $contentPos;
        $isLogo = str_contains($tag, 'custom-logo');
        if ($inContent && ! $isLogo && ! preg_match('#\b(loading|fetchpriority)=#i', $tag)) {
            if (! $firstContentDone && ! $hasPriority) {
                // First image in the content is the hero: load it first, never lazily
                $tag = preg_replace('#^<img\b#i', '<img fetchpriority="high"', $tag);
            } else {
                $tag = preg_replace('#^<img\b#i', '<img loading="lazy"', $tag);
                $imgFixes['lazy']++;
            }
        }
        if ($inContent && ! $isLogo) {
            $firstContentDone = true;
        }

        return $tag;
    }, $h, -1, $count, PREG_OFFSET_CAPTURE);
    foreach ($imgFixes as $k => $n) {
        if ($n) {
            $did[] = "img-$k:$n";
        }
    }

    // 4. og:image for pages that have none
    if (! preg_match('#property=["\']og:image["\']#i', $h)) {
        $img = null;
        if (preg_match('#"thumbnailUrl":"([^"]+)"#', $h, $m)) {
            $img = stripslashes($m[1]);
        } elseif ($contentPos !== false && preg_match('#<img\b[^>]*\bsrc=["\'](/images/[^"\']+)#i', substr($h, $contentPos), $m) && ! str_contains($m[1], 'logo')) {
            $img = $m[1];
        }
        $img ??= '/images/2026/07/photo-1570172619644-dfd03ed5d881.jpg';
        $tags = '<meta property="og:image" content="'.attr($img).'" />';
        if (($f = localImage($public, $img)) && ($size = @getimagesize($f))) {
            $tags .= "\n\t".'<meta property="og:image:width" content="'.$size[0].'" />'
                ."\n\t".'<meta property="og:image:height" content="'.$size[1].'" />'
                ."\n\t".'<meta property="og:image:type" content="'.$size['mime'].'" />';
        }
        if (! preg_match('#name=["\']twitter:card#i', $h)) {
            $tags .= "\n\t".'<meta name="twitter:card" content="summary_large_image" />';
        }
        $anchor = preg_match('#<meta property="og:site_name"[^>]*>#i', $h) ? '#(<meta property="og:site_name"[^>]*>)#i' : '#(</head>)#i';
        $h = preg_replace($anchor, $anchor === '#(</head>)#i' ? $tags."\n$1" : "$1\n\t".$tags, $h, 1);
        $did[] = 'og:image';
    }

    // 5. Font preconnect
    if (! str_contains($h, 'rel="preconnect" href="https://fonts.gstatic.com"')) {
        $h = preg_replace(
            '#(<head[^>]*>)#i',
            "$1\n<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\" />\n<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin />",
            $h,
            1
        );
        $did[] = 'preconnect';
    }

    // 6. Move jQuery out of <head> so it stops blocking the first paint
    $headEnd = stripos($h, '</head>');
    $jqRe = '#<script\b[^>]*id=["\']jquery-(core|migrate)-js["\'][^>]*></script>\s*#i';
    if (preg_match_all($jqRe, substr($h, 0, $headEnd), $jq)) {
        $bodyStart = stripos($h, '<body');
        // First external script in the body is where jQuery has to land
        if (preg_match('#<script\b[^>]*\bsrc=#i', $h, $first, PREG_OFFSET_CAPTURE, $bodyStart)) {
            $insertAt = $first[0][1];
            $between = substr($h, $headEnd, $insertAt - $headEnd);
            // Any inline jQuery use before that point would break; leave such pages alone
            $usesJq = preg_match_all('#<script(?![^>]*\bsrc=)[^>]*>(.*?)</script>#is', $between, $inl)
                && (bool) array_filter($inl[1], fn ($js) => preg_match('#\bjQuery\b|\$\(#', $js));
            if (! $usesJq && ! preg_match('#on\w+=["\'][^"\']*(jQuery|\$\()#i', $between)) {
                $tags = implode('', array_map('trim', $jq[0]));
                $h = substr($h, 0, $insertAt).$tags."\n".substr($h, $insertAt);
                $headPart = preg_replace($jqRe, '', substr($h, 0, $headEnd));
                $h = $headPart.substr($h, $headEnd);
                $did[] = 'jquery-to-footer';
            } else {
                $did[] = 'jquery-SKIPPED(inline use)';
            }
        }
    }

    if ($h !== $orig) {
        file_put_contents($file, $h);
        $log[] = str_pad($slug, 45).implode(', ', $did);
    }
}

echo implode("\n", $log)."\n\nChanged pages: ".count($log)."\n";
