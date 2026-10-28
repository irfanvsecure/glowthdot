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

        // The page's own stylesheet first, then the site-wide files from the layout
        $order = array_map(fn ($needle) => strpos($css, $needle), [
            '/* /css/pages/home.css */',
            '/* /css/site.css */',
            '/* /css/site-align.css */',
            '/* /css/home-polish.css */',
            '/* /css/home-v2.css */',
            '/* /css/v2.css */',
        ]);
        $this->assertNotContains(false, $order);
        $sorted = $order;
        sort($sorted);
        $this->assertSame($sorted, $order);

        $this->assertStringContainsString("@font-face {\n  font-family: 'Inter'", $css);
        // The phone-only base styles keep their media query
        $this->assertStringContainsString('@media (max-width:767px){', $css);
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

        preg_match('#<img[^>]*class="logo"[^>]*srcset="([^"]+)"#', $html, $m);
        $this->assertSame(
            '/images/2026/07/cropped-glowth-logo-transparent-205x48.png 205w, /images/2026/07/cropped-glowth-logo-transparent-410x95.png 410w',
            $m[1]
        );
        $this->assertFileExists(public_path('/images/2026/07/cropped-glowth-logo-transparent-410x95.png'));
    }
}
