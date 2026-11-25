<?php

namespace App\Http\Controllers;

use App\Support\SiteRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SiteController extends Controller
{
    /**
     * Any page, post or category archive, from the database (see App\Support\SiteRenderer).
     */
    public function show(Request $request, SiteRenderer $renderer): Response|RedirectResponse
    {
        if ($redirect = $this->publicPrefixRedirect($request)) {
            return $redirect;
        }

        $page = $renderer->render($request->getPathInfo(), app()->isLocal() && $request->has('__shot'));
        if ($page === null) {
            // An archive page that no longer exists (fewer pages than before): back to the archive
            if (preg_match('#^/category/([a-z0-9-]+)/page/\d+/?$#', $request->getPathInfo(), $m)
                && \App\Models\Category::where('slug', $m[1])->exists()) {
                return redirect()->away($request->getSchemeAndHttpHost().'/category/'.$m[1].'/', 301);
            }
            abort(404);
        }

        // ?goal=… and other query variants of a page: same content as the page itself (their canonical
        // says so); keep them out of the index so they are not counted as separate pages
        $variant = ! $page['noindex'] && array_diff(array_keys($request->query()), ['__shot']) !== [];
        $html = $variant
            ? preg_replace('#<meta\s+name=["\']robots["\']\s+content=["\'][^"\']*["\']#i', '<meta name="robots" content="noindex, follow"', $page['html'], 1)
            : $page['html'];

        return response($html, 200, array_filter([
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Robots-Tag' => $page['noindex'] ? 'noindex, nofollow' : ($variant ? 'noindex, follow' : null),
        ]));
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
}
