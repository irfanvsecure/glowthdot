<?php

namespace Tests\Feature;

use App\Http\Controllers\SiteController;
use Tests\TestCase;

class HiddenPagesTest extends TestCase
{
    public static function hiddenPages(): array
    {
        return array_map(fn ($slug) => [$slug], SiteController::HIDDEN_PAGES);
    }

    /** @dataProvider hiddenPages */
    public function test_hidden_page_is_still_served_but_noindex(string $slug): void
    {
        $response = $this->get('/'.$slug.'/');

        $response->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $html = $response->getContent();
        $this->assertSame(1, preg_match_all('#<meta\s+name=["\']robots["\']#i', $html));
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow" />', $html);
    }

    public function test_no_page_links_to_a_hidden_page(): void
    {
        $slugs = implode('|', SiteController::HIDDEN_PAGES);

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
        $this->assertStringContainsString('elementor-element-25d4237', $html);
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
        $sitemap = file_get_contents(public_path('page-sitemap.xml'));

        foreach (SiteController::HIDDEN_PAGES as $slug) {
            $this->assertStringNotContainsString('https://glowthdot.com/'.$slug.'/<', $sitemap);
        }
    }
}
