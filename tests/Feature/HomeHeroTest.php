<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeHeroTest extends TestCase
{
    public function test_hero_has_the_free_audit_card(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('class="elementor-element elementor-element-5bd9929 gh-hero ', $html);
        $this->assertSame(1, substr_count($html, 'class="gh-audit"'));
        $this->assertStringContainsString('<form class="gh-audit__form" action="/get-a-free-audit/" method="get">', $html);
        $this->assertStringContainsString('name="site"', $html);
        $this->assertStringContainsString('href="/get-a-free-audit/?site=none"', $html);
    }

    public function test_audit_ctas_use_one_name(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('Get My SEO Audit', $html);
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'Get My Free Growth Audit'));
    }

    public function test_funnel_picks_up_the_website_from_the_hero(): void
    {
        $html = $this->get('/get-a-free-audit/?site=myclinic.ae')->assertOk()->getContent();

        $this->assertStringContainsString('const params = new URLSearchParams(location.search);', $html);
        $this->assertStringContainsString("params.get('site')", $html);
    }
}
