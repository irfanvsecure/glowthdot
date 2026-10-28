<?php

namespace App\Http\Controllers;

use App\Support\Emoji;
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

    /**
     * A page of the site: resources/views/pages/{page}.blade.php, named by the route.
     */
    public function page(Request $request, PageSpeed $speed, Emoji $emoji, string $page): Response|RedirectResponse
    {
        if ($redirect = $this->publicPrefixRedirect($request)) {
            return $redirect;
        }

        $hidden = in_array($page, self::HIDDEN_PAGES, true);

        $html = view('pages.'.$page, ['noindex' => $hidden])->render();

        if (self::HIDDEN_PAGES !== []) {
            $html = $this->hideLinksToHiddenPages($html);
        }
        $html = $this->absoluteSeoUrls($html);
        $html = $emoji->wrap($html);

        // Local visual-regression screenshots (?__shot=1): no motion, so before/after compare pixel for pixel
        if (app()->isLocal() && $request->has('__shot')) {
            $html = preg_replace(
                '/<\/head>/i',
                '<style>*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important}'
                // Entrance states shown, so their content is compared too
                .'.gp-hero-step,.v2-r,.x-invisible{opacity:1!important;visibility:visible!important;transform:none!important}</style></head>',
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
     * No route matched.
     */
    public function notFound(Request $request): RedirectResponse
    {
        return $this->publicPrefixRedirect($request) ?? abort(404);
    }

    /**
     * When the domain serves the project folder, /public/... and /public/index.php also reach
     * the app; send them to the one real address of the page.
     */
    private function publicPrefixRedirect(Request $request): ?RedirectResponse
    {
        if (! preg_match('#^/public(/index\.php)?$#', $request->getBaseUrl())) {
            return null;
        }
        $target = preg_replace('#^/index\.php(?=/|$)#', '', $request->getPathInfo()) ?: '/';
        $query = $request->getQueryString();

        return redirect()->away($request->getSchemeAndHttpHost().$target.($query ? '?'.$query : ''), 301);
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
     * Search engines and social previews need full URLs; the pages write them site-relative.
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
            fn ($m) => $m[1].preg_replace('#"(\\/)(?!\\/)#', '"'.$jsonSite.'$1', $m[2]).$m[3],
            $html
        ) ?? $html;
    }
}
