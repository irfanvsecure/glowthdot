<?php

/**
 * Home page: the "Packages" section (three "Custom Quote" cards) becomes a problem picker.
 * Owners pick what costs them the most bookings and see what fixes it, with a link to that
 * service and a CTA that opens the audit funnel with the matching goal already chosen
 * (/get-a-free-audit/?goal=...).
 * Styles: public/css/home-polish.css (.gl-fix). Safe to re-run: an existing picker is replaced
 * with the current markup. Usage: php scripts/home-problem-picker.php
 */

$file = dirname(__DIR__).'/storage/app/live-html/home.html';
$h = $orig = file_get_contents($file);

// goal: one of the funnel's step 3 answers (resources/audit-funnel/funnel.html)
$problems = [
    [
        'id' => 'dms', 'icon' => '💬', 'tab' => 'Our DMs go unanswered', 'hint' => 'WhatsApp, Instagram, missed calls',
        'punch' => 'Clients don\'t wait. <span>They book whoever replies first.</span>',
        'fixes' => [
            'An AI receptionist answers WhatsApp &amp; Instagram in seconds, 24/7',
            'Books straight into your calendar and sends the reminders',
            'Hands the chat to your team whenever you want',
        ],
        'service' => ['AI Booking Bot', '/ai-appointment-booking-bot/'], 'goal' => 'AI booking bot',
    ],
    [
        'id' => 'google', 'icon' => '📍', 'tab' => 'Nobody finds us on Google', 'hint' => 'Maps, "near me", treatment searches',
        'punch' => 'Not in the top 3 on Maps? <span>Then you\'re invisible.</span>',
        'fixes' => [
            'Google Business Profile rebuilt to rank in your area',
            'Treatment pages for what UAE clients actually search',
            'Reviews and local citations that compound every month',
        ],
        'service' => ['SEO for beauty', '/seo-for-beauty-salons-clinics/'], 'goal' => 'Better Google rankings',
    ],
    [
        'id' => 'ads', 'icon' => '🔥', 'tab' => 'Our ads burn money', 'hint' => 'Clicks and likes, no bookings',
        'punch' => 'Clicks are cheap. <span>Booked appointments are the only metric.</span>',
        'fixes' => [
            'Google &amp; Meta campaigns built per treatment, not for "awareness"',
            'Landing pages that send people to WhatsApp or the booking form',
            'Weekly tuning against booked appointments, not likes',
        ],
        'service' => ['Google Ads', '/google-ads-for-beauty/'], 'goal' => 'Fix my ad spend',
    ],
    [
        'id' => 'chairs', 'icon' => '🪑', 'tab' => 'Chairs sit empty midweek', 'hint' => 'Slow Tuesdays, gaps in the calendar',
        'punch' => 'An empty Tuesday chair <span>is revenue you never get back.</span>',
        'fixes' => [
            'Reels and posts that sell a treatment, not just a vibe',
            'Offers to past clients, timed for your slow days',
            'Rebooking nudges that bring regulars back sooner',
        ],
        'service' => ['Social media management', '/social-media-management-for-beauty/'], 'goal' => 'More bookings',
    ],
    [
        'id' => 'site', 'icon' => '📱', 'tab' => 'Our website doesn\'t book', 'hint' => 'Slow, pretty, no enquiries',
        'punch' => 'A pretty site that doesn\'t book <span>is just a brochure.</span>',
        'fixes' => [
            'Fast, mobile-first pages for every treatment',
            'WhatsApp and booking one tap away on every screen',
            'Built to rank for the searches your clients make',
        ],
        'service' => ['Websites &amp; landing pages', '/websites-landing-pages-for-beauty/'], 'goal' => 'A new website',
    ],
];

$tabs = '';
$panels = '';
foreach ($problems as $i => $p) {
    $on = $i === 0;
    $tabs .= '<button type="button" class="gl-fix__tab" role="tab" id="gl-fix-tab-'.$p['id'].'" aria-controls="gl-fix-'.$p['id'].'"'
        .' aria-selected="'.($on ? 'true' : 'false').'" tabindex="'.($on ? '0' : '-1').'">'
        .'<span class="gl-fix__icon" aria-hidden="true">'.$p['icon'].'</span>'
        .'<span class="gl-fix__tabtext"><b>'.$p['tab'].'</b><small>'.$p['hint'].'</small></span></button>'."\n";

    $fixes = implode('', array_map(fn ($f) => '<li>'.$f.'</li>', $p['fixes']));
    $panels .= '<div class="gl-fix__panel" role="tabpanel" id="gl-fix-'.$p['id'].'" aria-labelledby="gl-fix-tab-'.$p['id'].'" tabindex="0"'.($on ? '' : ' hidden').'>'
        .'<p class="gl-fix__punch">'.$p['punch'].'</p>'
        .'<p class="gl-fix__what">What we do about it</p>'
        .'<ul class="gl-fix__list">'.$fixes.'</ul>'
        .'<div class="gl-fix__foot">'
        .'<a class="gl-fix__cta" href="/get-a-free-audit/?goal='.rawurlencode($p['goal']).'">Fix this first — free audit'
        .'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>'
        .'<a class="gl-fix__more" href="'.$p['service'][1].'">How '.$p['service'][0].' works →</a>'
        .'</div></div>'."\n";
}

$section = <<<HTML
<section class="gl-fix" id="find-your-fix" aria-labelledby="gl-fix-title">
  <div class="gl-fix__in">
    <div class="gl-fix__head">
      <span class="gl-fix__kicker">Find your fix</span>
      <h2 class="gl-fix__title" id="gl-fix-title">Tell us what's hurting. <span>We'll show you what fixes it.</span></h2>
      <p class="gl-fix__intro">No packages to decode, no "custom quote" guessing. Pick the problem that costs you the most bookings and start there.</p>
    </div>
    <div class="gl-fix__body">
      <div class="gl-fix__side">
        <div class="gl-fix__tabs" role="tablist" aria-label="Your biggest problem">
{$tabs}        </div>
        <p class="gl-fix__unsure">Not sure where it's leaking? <a href="/get-a-free-audit/?goal=Not%20sure%20yet">The free audit finds it →</a></p>
      </div>
      <div class="gl-fix__panels">
{$panels}      </div>
    </div>
  </div>
  <script>
  (function () {
    var root = document.getElementById('find-your-fix');
    if (!root) return;
    var tabs = [].slice.call(root.querySelectorAll('[role="tab"]'));
    function select(tab, focus) {
      tabs.forEach(function (t) {
        var on = t === tab;
        t.setAttribute('aria-selected', on ? 'true' : 'false');
        t.tabIndex = on ? 0 : -1;
        document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
      });
      if (focus) tab.focus();
    }
    tabs.forEach(function (t, i) {
      t.addEventListener('click', function () { select(t); });
      t.addEventListener('keydown', function (e) {
        var k = e.key, n = null;
        if (k === 'ArrowDown' || k === 'ArrowRight') n = tabs[(i + 1) % tabs.length];
        if (k === 'ArrowUp' || k === 'ArrowLeft') n = tabs[(i - 1 + tabs.length) % tabs.length];
        if (k === 'Home') n = tabs[0];
        if (k === 'End') n = tabs[tabs.length - 1];
        if (n) { e.preventDefault(); select(n, true); }
      });
    });
  })();
  </script>
</section>
HTML;

if (preg_match('#<section class="gl-(?:fix|leak)"#', $h)) {
    // Earlier picker (or the calculator it replaced): no nested sections inside, so the first </section> after its script ends it
    $h = preg_replace('#<section class="gl-(?:fix|leak)".*?</script>\s*</section>#s', trim($section), $h, 1);
} else {
    // Remove the whole Packages container (div depth count) and put the picker in its place
    $start = strpos($h, '<div class="elementor-element elementor-element-5aea8b3 ');
    if ($start === false) {
        fwrite(STDERR, "packages section not found\n");
        exit(1);
    }
    $pos = $start + 4;
    $depth = 1;
    $m = [];
    while ($depth > 0 && preg_match('#<(/?)div\b#', $h, $m, PREG_OFFSET_CAPTURE, $pos)) {
        $depth += $m[1][0] === '/' ? -1 : 1;
        $pos = $m[0][1] + strlen($m[0][0]);
    }
    $end = strpos($h, '>', $pos) + 1;
    $h = substr($h, 0, $start).trim($section).substr($h, $end);
}

if ($h === $orig) {
    echo "problem picker: already up to date\n";
} else {
    file_put_contents($file, $h);
    echo "problem picker: updated\n";
}
