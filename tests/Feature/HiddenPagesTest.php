<?php

namespace Tests\Feature;

use App\Models\Page;
use Tests\TestCase;

class HiddenPagesTest extends TestCase
{
    /** The industry pages, hidden for now (status "hidden") */
    private function hiddenSlugs(): array
    {
        return Page::where('status', 'hidden')->pluck('slug')->all();
    }

    public function test_hidden_pages_are_still_served_but_noindex(): void
    {
        $this->assertCount(27, $this->hiddenSlugs());
        foreach ($this->hiddenSlugs() as $slug) {
            $response = $this->get('/'.$slug.'/');

            $response->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
            $html = $response->getContent();
            $this->assertSame(1, preg_match_all('#<meta\s+name=["\']robots["\']#i', $html), $slug);
            $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        }
    }

    public function test_no_page_links_to_a_hidden_page(): void
    {
        $slugs = implode('|', $this->hiddenSlugs());

        foreach (['/', '/about-us/', '/dubai/', '/seo-for-beauty-salons-clinics/', '/industries/', '/beauty-salons/'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertDoesNotMatchRegularExpression(
                '~<a\b[^>]*\bhref=["\'](?:https?://(?:www\.)?glowthdot\.com)?/(?:'.$slugs.')/?["\'#?]~i',
                $html,
                "$url still links to a hidden page"
            );
        }
    }

    public function test_industry_cards_on_the_home_page_go_to_the_free_audit(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertGreaterThanOrEqual(10, substr_count($html, 'href="/get-a-free-audit/"'));
        // The footer "Industries" column stays, its links go to the audit too
        $this->assertStringContainsString('k-25d4237', $html);
        $this->assertStringNotContainsString('25d4237{display:none', $html);
    }

    public function test_visible_pages_stay_indexable(): void
    {
        $response = $this->get('/dubai/');

        $response->assertOk()->assertHeaderMissing('X-Robots-Tag');
        $this->assertStringNotContainsString('noindex', $response->getContent());
    }

    public function test_hidden_pages_are_not_in_the_sitemap(): void
    {
        $sitemap = $this->get('/page-sitemap.xml')->assertOk()->getContent();

        foreach ($this->hiddenSlugs() as $slug) {
            $this->assertStringNotContainsString('https://glowthdot.com/'.$slug.'/<', $sitemap);
        }
        $this->assertStringContainsString('https://glowthdot.com/industries/<', $sitemap);
    }

    public function test_industries_hub_stays_visible_and_is_in_the_header(): void
    {
        $response = $this->get('/industries/');
        $response->assertOk()->assertHeaderMissing('X-Robots-Tag');
        $this->assertStringNotContainsString('noindex', $response->getContent());

        foreach (['/', '/about-us/', '/dubai/', '/seo-for-beauty-salons-clinics/', '/industries/'] as $url) {
            $this->assertStringContainsString(
                '<a href="/industries/"',
                $this->get($url)->getContent(),
                "$url has no Industries menu item"
            );
        }
    }
}
