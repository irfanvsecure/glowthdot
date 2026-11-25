<?php

namespace App\Http\Controllers;

use App\Support\LlmsTxt;
use App\Support\Sitemap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * sitemap_index.xml, page-, post- and category-sitemap.xml and robots.txt, built from the
 * database on each content version, so they follow every publish, edit and delete.
 */
class SitemapController extends Controller
{
    public function show(Sitemap $sitemap, string $name): Response
    {
        $xml = $sitemap->xml($name);
        abort_if($xml === null, 404);

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        return response((string) setting('robots_txt', "User-agent: *\nDisallow:\n"), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function llms(LlmsTxt $llms): Response
    {
        return response($llms->text(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * WhatsApp links on the pages go through /whatsapp/ (App\Support\SiteRenderer): robots.txt keeps
     * crawlers off it, so site checkers stop reporting wa.me's rate limit (429) as a broken link.
     */
    public function whatsapp(Request $request): RedirectResponse
    {
        $query = $request->getQueryString();

        return redirect()->away('https://wa.me/'.setting('whatsapp').($query ? '?'.$query : ''), 302)
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
