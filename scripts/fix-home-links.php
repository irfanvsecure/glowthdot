<?php

$base = dirname(__DIR__);
$htmlDir = $base.'/storage/app/live-html';

$titleToSlug = [
    'Arabic Beauty Salon' => 'arabic-beauty-salon',
    'Russian Beauty Salon' => 'russian-beauty-salon',
    'Korean Beauty Salon' => 'korean-beauty-salon',
    'Hair Salons' => 'hair-salons',
    'Ladies Salons' => 'ladies-salon-marketing',
    'Barber Shops' => 'barber-shops',
    'Nail Salons' => 'nail-salons',
    'Day Spas' => 'day-spas',
    'Medical Spas' => 'medical-spas',
    'Aesthetic Clinics' => 'aesthetic-clinics',
    'Dermatology Clinics' => 'dermatology-clinics',
    'Cosmetic Clinics' => 'aesthetic-clinics',
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

function dumpTitles(string $html): void
{
    if (! preg_match_all('/<p class="elementor-icon-box-title">(.*?)<\/p>/is', $html, $m)) {
        echo "No icon-box titles\n";

        return;
    }
    foreach ($m[1] as $inner) {
        $linked = stripos($inner, '<a ') !== false;
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($inner))) ?? '');
        $href = '';
        if (preg_match('/href=["\']([^"\']+)["\']/', $inner, $hm)) {
            $href = $hm[1];
        }
        echo ($linked ? "[$href] " : '[NO] ').$text."\n";
    }
}

foreach (['industries', 'home'] as $slug) {
    echo "==== $slug BEFORE/CURRENT ====\n";
    dumpTitles(file_get_contents($htmlDir.'/'.$slug.'.html') ?: '');
}

// Fix home + industries: also wrap icon (make whole box clickable via wrapping elementor-icon-box-wrapper)
function linkAll(string $html, array $map, string $htmlDir): array
{
    $n = 0;
    $html = preg_replace_callback(
        '/(<p class="elementor-icon-box-title">)(.*?)(<\/p>)/is',
        function ($m) use ($map, $htmlDir, &$n) {
            $inner = $m[2];
            $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($inner))) ?? '');
            if (! isset($map[$text])) {
                return $m[0];
            }
            $slug = $map[$text];
            if (! is_file($htmlDir.'/'.$slug.'.html')) {
                return $m[0];
            }
            $href = '/'.$slug.'/';
            // replace existing wrong/missing link
            if (preg_match('/<a\b[^>]*>/i', $inner)) {
                $inner2 = preg_replace('/<a\b[^>]*>/i', '<a href="'.$href.'">', $inner, 1);
                $inner2 = preg_replace('/<\/a>/i', '</a>', $inner2); // keep
                if ($inner2 !== null && $inner2 !== $inner) {
                    $n++;

                    return $m[1].$inner2.$m[3];
                }

                return $m[0];
            }
            $n++;

            return $m[1].'<a href="'.$href.'">'.$inner.'</a>'.$m[3];
        },
        $html
    ) ?? $html;

    return [$html, $n];
}

foreach (['home', 'industries'] as $slug) {
    $file = $htmlDir.'/'.$slug.'.html';
    $html = file_get_contents($file) ?: '';
    [$html, $n] = linkAll($html, $titleToSlug, $htmlDir);
    file_put_contents($file, $html);
    echo "\n==== $slug linked/updated: $n ====\n";
    dumpTitles($html);
}

// Ensure barber on industries
$ind = file_get_contents($htmlDir.'/industries.html') ?: '';
echo "\nBarber present: ".(stripos($ind, 'barber-shops') !== false ? 'yes' : 'no')."\n";
echo 'Day spas: '.(stripos($ind, 'day-spas') !== false ? 'yes' : 'no')."\n";
