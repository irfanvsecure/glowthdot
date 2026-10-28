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
            abort(404);
        }

        return response($page['html'], 200, array_filter([
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Robots-Tag' => $page['noindex'] ? 'noindex, nofollow' : null,
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
