<?php

namespace Tests\Feature;

use Tests\TestCase;

class PageSpeedTest extends TestCase
{
    private function pageHead(string $uri): string
    {
        return explode('</head>', $this->get($uri)->assertOk()->getContent())[0];
    }

    public function test_head_loads_one_local_stylesheet_and_no_google_fonts(): void
    {
        $head = $this->pageHead('/');

        preg_match_all("#<link rel='stylesheet'[^>]*href='([^']+)'#", $head, $sheets);
        $this->assertCount(1, $sheets[1]);
        $this->assertMatchesRegularExpression('#^/css/bundle/[0-9a-f]{16}\.css$#', $sheets[1][0]);
        $this->assertFileExists(public_path($sheets[1][0]));
        $this->assertStringNotContainsString('fonts.googleapis.com', $head);
        $this->assertStringNotContainsString('<style', $head);
    }

    public function test_bundle_keeps_the_cascade_order_and_the_mobile_media_query(): void
    {
        preg_match("#href='(/css/bundle/[^']+)'#", $this->pageHead('/'), $m);
        $css = file_get_contents(public_path($m[1]));

        $order = array_map(fn ($needle) => strpos($css, $needle), [
            '/* /assets/themes/astra/assets/css/minified/main.min.css */',
            ':root{--ast-post-nav-space:0',                // astra inline style after its file
            "@font-face {\n  font-family: 'Inter'",       // where the first Google Fonts link was
            '/* /images/elementor/css/post-8.css */',
            '/* glowth-hero-fallback */',
            '/* /css/home-polish.css */',                  // injected last by SiteController
        ]);
        $this->assertNotContains(false, $order);
        $sorted = $order;
        sort($sorted);
        $this->assertSame($sorted, $order);

        $this->assertStringContainsString("/* /images/elementor/css/base-mobile.css */\n@media (max-width:767px){", $css);
    }

    public function test_hero_image_and_fonts_are_requested_early(): void
    {
        $head = $this->pageHead('/');
        $bundle = strpos($head, 'glowth-bundle-css');

        $hero = strpos($head, '<link rel="preload" href="/images/2026/07/photo-1522337660859-02fbefca4702.jpg" as="image" fetchpriority="high">');
        $font = strpos($head, '<link rel="preload" href="/fonts/inter-latin.woff2" as="font" type="font/woff2" crossorigin>');
        $this->assertNotFalse($hero);
        $this->assertNotFalse($font);
        $this->assertLessThan($bundle, $hero);
        $this->assertLessThan($bundle, $font);
        $this->assertFileExists(public_path('/fonts/inter-latin.woff2'));
    }

    public function test_logo_offers_only_1x_and_2x_files(): void
    {
        $html = $this->get('/')->getContent();

        preg_match('#<img[^>]*class="custom-logo"[^>]*srcset="([^"]+)"#', $html, $m);
        $this->assertSame(
            '/images/2026/07/cropped-glowth-logo-transparent-205x48.png 205w, /images/2026/07/cropped-glowth-logo-transparent-410x95.png 410w',
            $m[1]
        );
        $this->assertFileExists(public_path('/images/2026/07/cropped-glowth-logo-transparent-410x95.png'));
    }
}
