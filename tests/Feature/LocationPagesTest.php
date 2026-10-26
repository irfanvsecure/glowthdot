<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocationPagesTest extends TestCase
{
    public static function pages(): array
    {
        return [['dubai'], ['abu-dhabi'], ['sharjah']];
    }

    /**
     * The snapshots are rendered from storage/app/location-content/{slug}.json by
     * scripts/build-location-pages.php; this guards the result.
     *
     * @dataProvider pages
     */
    public function test_location_page_renders_the_new_layout(string $slug): void
    {
        $html = $this->get('/'.$slug.'/')->assertOk()->getContent();
        $data = json_decode(file_get_contents(storage_path('app/location-content/'.$slug.'.json')), true);

        $this->assertStringContainsString('class="lp" id="lp-'.$slug.'"', $html);
        $this->assertStringNotContainsString("class='gwd'", $html);
        $this->assertStringNotContainsString('class="gwd"', $html);
        $this->assertSame(1, substr_count($html, '<h1'));

        // Every in-page nav link has a target
        preg_match_all('#<nav class="lp-nav".*?</nav>#s', $html, $nav);
        preg_match_all('#href="\#([a-z-]+)"#', $nav[0][0], $anchors);
        $this->assertNotEmpty($anchors[1]);
        foreach ($anchors[1] as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $html, "#$id");
        }

        // Every service, area and question from the JSON is on the page
        $this->assertSame(count($data['services']['cards']), substr_count($html, 'class="lp-service"'));
        $this->assertSame(count($data['areas']['items']), substr_count($html, 'role="tabpanel"'));
        $this->assertSame(count($data['faq']['items']), substr_count($html, '<summary>'));

        // FAQPage schema matches the visible questions
        preg_match('#<script type="application/ld\+json" id="glowth-page-faq">(.*?)</script>#s', $html, $schema);
        $faq = json_decode($schema[1] ?? '', true);
        $this->assertSame('FAQPage', $faq['@type'] ?? null);
        $this->assertCount(count($data['faq']['items']), $faq['mainEntity']);

        // The layout's stylesheet is bundled into the head and its script is loaded
        $this->assertStringContainsString('/js/location.js?v=', $html);
        $bundle = preg_match("#href='(/css/bundle/[^']+)'#", $html, $m) ? file_get_contents(public_path($m[1])) : '';
        $this->assertStringContainsString('.lp-hero', $bundle);
    }
}
