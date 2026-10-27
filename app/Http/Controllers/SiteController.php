<?php

namespace App\Http\Controllers;

use App\Support\PageSpeed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SiteController extends Controller
{
    /**
     * Industry pages, hidden for now: still served, but noindex, and every link to them on the
     * site (cards, content, footer, the /industries/ hub) goes to the free audit instead.
     * Empty the list to bring them back.
     */
    public const HIDDEN_PAGES = [
        'beauty-salons', 'aesthetic-clinics', 'hair-salons', 'nail-salons', 'day-spas',
        'medical-spas', 'dermatology-clinics', 'laser-hair-removal-clinics', 'barber-shops',
        'lash-studios', 'brow-studios', 'makeup-studios', 'beauty-academies', 'wellness-centers',
        'arabic-beauty-salon', 'cosmetic-makeup-beauty-brand', 'hair-care-beauty-brand',
        'hair-extension-studios', 'hammam-center', 'korean-beauty-salon', 'ladies-salon-marketing',
        'massage-centers', 'moroccan-bath-centre', 'plastic-surgery-clinics', 'russian-beauty-salon',
        'skincare-beauty-brand', 'skin-care-clinics',
    ];

    private const HIDDEN_LINK_TARGET = '/get-a-free-audit/';

    public function show(Request $request, PageSpeed $speed, ?string $path = null): Response|RedirectResponse
    {
        // When the domain serves the project folder, /public/... and /public/index.php also
        // reach the app; send them to the one real address of the page.
        if (preg_match('#^/public(/index\.php)?$#', $request->getBaseUrl())) {
            $target = preg_replace('#^/index\.php(?=/|$)#', '', $request->getPathInfo()) ?: '/';
            $query = $request->getQueryString();

            return redirect()->away($request->getSchemeAndHttpHost().$target.($query ? '?'.$query : ''), 301);
        }

        $path = trim((string) $path, '/');
        $path = str_replace('\\', '/', $path);

        if ($path !== '' && (str_contains($path, '..') || str_contains($path, "\0"))) {
            abort(404);
        }

        $slug = $path === '' ? 'home' : str_replace('/', '-', $path);
        $file = storage_path('app/live-html/'.$slug.'.html');

        if (! is_file($file)) {
            abort(404);
        }

        $base = str_replace('\\', '/', realpath(storage_path('app/live-html')) ?: '');
        $real = str_replace('\\', '/', realpath($file) ?: '');

        if ($base === '' || $real === '' || ! str_starts_with($real, $base)) {
            abort(404);
        }

        $html = file_get_contents($real) ?: '';

        $injectHead = '';
        // Site-wide: align sections with header left/right gutters
        if (! str_contains($html, 'site-align.css')) {
            $injectHead .= '<link rel="stylesheet" href="'.$this->asset('/css/site-align.css').'" />';
        }
        // Home only: richer UI polish (content unchanged)
        if ($slug === 'home' && ! str_contains($html, 'home-polish.css')) {
            $injectHead .= '<link rel="stylesheet" href="'.$this->asset('/css/home-polish.css').'" />';
        }
        $hidden = in_array($slug, self::HIDDEN_PAGES, true);
        if (self::HIDDEN_PAGES !== []) {
            $html = $this->hideLinksToHiddenPages($html);
        }
        if ($hidden) {
            $html = preg_replace('#<meta\s+name=["\']robots["\'][^>]*>\s*#i', '', $html) ?? $html;
            $injectHead .= '<meta name="robots" content="noindex, nofollow" />';
        }
        if ($injectHead !== '') {
            $html = preg_replace('/<\/head>/i', $injectHead.'</head>', $html, 1) ?? $html;
        }
        $html = $this->absoluteSeoUrls($html);

        if ($slug === 'home' && ! str_contains($html, 'home-polish.js')) {
            $html = preg_replace(
                '/<\/body>/i',
                '<script src="'.$this->asset('/js/home-polish.js').'" defer></script></body>',
                $html,
                1
            ) ?? $html;
        }

        $html = $speed->optimize($html);

        return response($html, 200, array_filter([
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Robots-Tag' => $hidden ? 'noindex, nofollow' : null,
        ]));
    }

    /**
     * Links (not canonical tags or schema) to a hidden page point at the free audit instead.
     */
    private function hideLinksToHiddenPages(string $html): string
    {
        $slugs = implode('|', array_map(fn ($s) => preg_quote($s, '#'), self::HIDDEN_PAGES));

        return preg_replace(
            '#(<a\b[^>]*?\bhref=)(["\'])(?:https?://(?:www\.)?glowthdot\.com)?/(?:'.$slugs.')/?(?:[?\#][^"\']*)?\2#i',
            '$1$2'.self::HIDDEN_LINK_TARGET.'$2',
            $html
        ) ?? $html;
    }

    /**
     * Permanent redirect for an old URL; the route passes the new page's path as "to".
     */
    public function moved(string $to): RedirectResponse
    {
        // url() drops trailing slashes; pages live at the slash form
        return redirect()->away(url($to).'/', 301);
    }

    /**
     * Versioned URL, so the long browser cache in public/.htaccess never serves an old copy.
     */
    private function asset(string $path): string
    {
        $mtime = @filemtime(public_path($path));

        return $mtime ? $path.'?v='.$mtime : $path;
    }

    /**
     * Search engines and social previews need full URLs; the mirror stores them site-relative.
     */
    private function absoluteSeoUrls(string $html): string
    {
        $site = config('app.canonical_url');

        // canonical, prev/next, og:url, og:image, twitter:image
        $html = preg_replace(
            '#(<link\s+rel=["\'](?:canonical|prev|next)["\']\s+href=["\']|<meta\s+(?:property|name)=["\'](?:og:url|og:image|twitter:image)["\']\s+content=["\'])/(?!/)#i',
            '$1'.$site.'/',
            $html
        ) ?? $html;

        // schema.org JSON-LD stores URLs with escaped slashes: "url":"\/about-us\/"
        $jsonSite = str_replace('/', '\/', $site);

        return preg_replace_callback(
            '#(<script type="application/ld\+json"[^>]*>)(.*?)(</script>)#s',
            fn ($m) => $m[1].preg_replace('#"(\\\\/)(?!\\\\/)#', '"'.$jsonSite.'$1', $m[2]).$m[3],
            $html
        ) ?? $html;
    }
}
