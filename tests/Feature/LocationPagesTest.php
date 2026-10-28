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
     * resources/views/pages/{slug}.blade.php
     *
     * @dataProvider pages
     */
    public function test_location_page_renders_the_new_layout(string $slug): void
    {
        $html = $this->get('/'.$slug.'/')->assertOk()->getContent();

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

        // Services, areas (one tab panel per tab) and questions are on the page
        $this->assertGreaterThan(0, substr_count($html, 'class="lp-service"'));
        $this->assertSame(substr_count($html, 'role="tab"'), substr_count($html, 'role="tabpanel"'));
        $this->assertGreaterThan(0, substr_count($html, '<summary>'));

        // FAQPage schema matches the visible questions
        preg_match('#<script type="application/ld\+json" id="glowth-page-faq">(.*?)</script>#s', $html, $schema);
        $faq = json_decode($schema[1] ?? '', true);
        $this->assertSame('FAQPage', $faq['@type'] ?? null);
        $this->assertCount(substr_count($html, '<summary>'), $faq['mainEntity']);

        // The layout's stylesheet is bundled into the head and its script is loaded
        $this->assertStringContainsString('/js/location.js?v=', $html);
        $bundle = preg_match("#href='(/css/bundle/[^']+)'#", $html, $m) ? file_get_contents(public_path($m[1])) : '';
        $this->assertStringContainsString('.lp-hero', $bundle);
    }
}
