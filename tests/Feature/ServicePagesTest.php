<?php

namespace Tests\Feature;

use Tests\TestCase;

class ServicePagesTest extends TestCase
{
    public static function pages(): array
    {
        return array_map(fn ($s) => [$s], [
            'seo-for-beauty-salons-clinics', 'websites-landing-pages-for-beauty', 'social-media-management-for-beauty',
            'google-ads-for-beauty', 'instagram-facebook-ads-for-beauty', 'ai-appointment-booking-bot',
            'ecommerce-for-beauty', 'reviews-reputation',
        ]);
    }

    /**
     * The page from the database (pages + page_sections), as imported by site:import
     *
     * @dataProvider pages
     */
    public function test_service_page_renders_the_new_layout(string $slug): void
    {
        $html = $this->get('/'.$slug.'/')->assertOk()->getContent();

        $this->assertStringContainsString('class="lp lp--service" id="lp-'.$slug.'"', $html);
        $this->assertSame(1, substr_count($html, '<h1'));

        // Every in-page link points at something on the page
        preg_match('#<main class="lp lp--service".*?</main>#s', $html, $main);
        preg_match_all('#href="\#([A-Za-z0-9_-]+)"#', $main[0], $anchors);
        foreach (array_unique($anchors[1]) as $id) {
            $this->assertMatchesRegularExpression('#id="'.preg_quote($id, '#').'"#', $html, "#$id");
        }

        // The lead form keeps the field ids the lead handler reads
        $this->assertStringContainsString('id="lp-form"', $html);
        $this->assertMatchesRegularExpression('#<div class="lp-formcta__form">.*?w-form.*?name="post_id".*?name="form_id"#s', $html);

        // Kept original markup is scoped to the page's document class, so its CSS applies
        $this->assertMatchesRegularExpression('#class="[^"]*\blp-raw x x-\d+"#', $html);

        // FAQPage schema matches the visible questions
        preg_match('#<script type="application/ld\+json" id="glowth-page-faq">(.*?)</script>#s', $html, $schema);
        $this->assertGreaterThan(0, substr_count($main[0], '<summary>'));
        $this->assertCount(substr_count($main[0], '<summary>'), json_decode($schema[1] ?? '[]', true)['mainEntity'] ?? []);

        // Both stylesheets are bundled into the head and the page script is loaded
        $this->assertStringContainsString('/js/location.js?v=', $html);
        $bundle = preg_match("#href='(/css/bundle/[^']+)'#", $html, $m) ? file_get_contents(public_path($m[1])) : '';
        $this->assertStringContainsString('.lp-hero', $bundle);
        $this->assertStringContainsString('.lp-formcta', $bundle);
    }
}
