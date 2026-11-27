<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SeoMeta;
use App\Services\OgCards;
use Tests\Concerns\CallsMcp;
use Tests\TestCase;

/** og:type and og:image from the site-checker report of Nov 2026 */
class ShareImagesTest extends TestCase
{
    use CallsMcp;

    private function og(string $html, string $property): ?string
    {
        return preg_match('#<meta property="'.preg_quote($property, '#').'" content="([^"]*)"#', $html, $m) ? $m[1] : null;
    }

    public function test_service_location_and_category_pages_are_websites_and_posts_articles(): void
    {
        foreach (['/google-ads-for-beauty/', '/dubai/', '/', '/category/salon-marketing/'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertSame('website', $this->og($html, 'og:type'), $path);
            $this->assertNull($this->og($html, 'article:modified_time'), $path);
        }
        $this->assertSame('article', $this->og($this->get('/salon-marketing-dubai-guide/')->getContent(), 'og:type'));
    }

    public function test_every_page_and_archive_gets_its_own_share_image(): void
    {
        app(OgCards::class)->refreshAll();

        $shared = SeoMeta::selectRaw('og_image, count(*) as c')->groupBy('og_image')->having('c', '>', 1)->pluck('og_image');
        $this->assertSame([], $shared->all());
        $this->assertSame(0, SeoMeta::whereNull('og_image')->count());

        $html = $this->get('/dubai/')->getContent();
        $image = $this->og($html, 'og:image');
        $this->assertMatchesRegularExpression('#^https://glowthdot\.com/images/og/dubai-[0-9a-f]{8}\.png$#', $image);
        $this->assertFileExists(public_path(parse_url($image, PHP_URL_PATH)));
        $this->assertSame('1200', $this->og($html, 'og:image:width'));
        $this->assertSame('630', $this->og($html, 'og:image:height'));

        // posts keep their own covers
        $this->assertStringContainsString('/images/2026/', $this->og($this->get('/salon-marketing-dubai-guide/')->getContent(), 'og:image'));
    }

    public function test_new_and_renamed_pages_get_a_fresh_card(): void
    {
        $this->tool('create_page', ['title' => 'Share Card Test Page', 'slug' => 'share-card-test', 'status' => 'published']);
        $first = Page::where('slug', 'share-card-test')->first()->seo->og_image;
        $this->assertStringStartsWith('/images/og/share-card-test-', $first);
        $this->assertFileExists(public_path($first));
        $this->assertSame('website', Page::where('slug', 'share-card-test')->first()->seo->og_type);

        $this->tool('update_page', ['slug' => 'share-card-test', 'title' => 'Share Card Test Page Renamed']);
        $second = Page::where('slug', 'share-card-test')->first()->seo->og_image;
        $this->assertNotSame($first, $second);
        $this->assertFileExists(public_path($second));
        $this->assertFileDoesNotExist(public_path($first));
    }
}
