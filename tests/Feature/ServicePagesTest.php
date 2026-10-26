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
     * The snapshots are rendered from storage/app/service-content/{slug}.json by
     * scripts/build-service-pages.php; this guards the result.
     *
     * @dataProvider pages
     */
    public function test_service_page_renders_the_new_layout(string $slug): void
    {
        $html = $this->get('/'.$slug.'/')->assertOk()->getContent();
        $data = json_decode(file_get_contents(storage_path('app/service-content/'.$slug.'.json')), true);

        $this->assertStringContainsString('class="lp lp--service" id="lp-'.$slug.'"', $html);
        $this->assertSame(1, substr_count($html, '<h1'));

        // Every in-page link points at something on the page
        preg_match('#<main class="lp lp--service".*?</main>#s', $html, $main);
        preg_match_all('#href="\#([A-Za-z0-9_-]+)"#', $main[0], $anchors);
        foreach (array_unique($anchors[1]) as $id) {
            $this->assertMatchesRegularExpression('#id="'.preg_quote($id, '#').'"#', $html, "#$id");
        }

        // The lead form is still Elementor's, with the ids the lead handler reads
        $this->assertStringContainsString('id="lp-form"', $html);
        $this->assertMatchesRegularExpression('#<div class="lp-formcta__form">.*?elementor-widget-form.*?name="post_id".*?name="form_id"#s', $html);

        // Kept Elementor markup is scoped to the page's Elementor document, so its CSS applies
        $this->assertStringContainsString('class="lp-raw elementor elementor-'.$data['docId'].'"', $html);

        // FAQPage schema matches the visible questions
        $faq = array_merge(...array_map(fn ($s) => $s['items'], array_filter($data['sections'], fn ($s) => $s['layout'] === 'faq')));
        preg_match('#<script type="application/ld\+json" id="glowth-page-faq">(.*?)</script>#s', $html, $schema);
        $this->assertCount(count($faq), json_decode($schema[1] ?? '[]', true)['mainEntity'] ?? []);

        // Both stylesheets are bundled into the head and the page script is loaded
        $this->assertStringContainsString('/js/location.js?v=', $html);
        $bundle = preg_match("#href='(/css/bundle/[^']+)'#", $html, $m) ? file_get_contents(public_path($m[1])) : '';
        $this->assertStringContainsString('.lp-hero', $bundle);
        $this->assertStringContainsString('.lp-formcta', $bundle);
    }
}
