<?php

namespace Tests\Feature\Mcp;

use Illuminate\Support\Facades\Http;
use Tests\Concerns\CallsMcp;
use Tests\TestCase;

class SeoToolsTest extends TestCase
{
    use CallsMcp;

    public function test_get_and_update_seo_tags(): void
    {
        $seo = $this->tool('get_seo', ['path' => '/dubai/'])['seo'];
        $this->assertSame('/dubai/', $seo['canonical']);

        $result = $this->tool('update_seo', ['path' => '/dubai/', 'title' => 'Dubai Beauty Marketing Agency | Glowth', 'description' => 'short', 'robots' => 'noindex, follow']);
        $this->assertContains('Description is 5 characters (70-160 shows best).', $result['warnings']);
        $this->get('/dubai/')->assertSee('<title>Dubai Beauty Marketing Agency | Glowth</title>', false)
            ->assertSee('<meta name="description" content="short" />', false)
            ->assertSee('<meta name="robots" content="noindex, follow">', false);

        $this->tool('update_seo', ['type_of' => 'post', 'slug' => 'salon-marketing-dubai-guide', 'og_image' => '/images/2026/10/salon-marketing-calendar-dubai-cover.webp']);
        $this->get('/salon-marketing-dubai-guide/')->assertSee('content="https://glowthdot.com/images/2026/10/salon-marketing-calendar-dubai-cover.webp"', false);

        $this->assertStringContainsString('robots', $this->tool('update_seo', ['path' => '/dubai/', 'robots' => 'please index'], error: true)['error']);
        $this->assertStringContainsString('Nothing lives at', $this->tool('get_seo', ['path' => '/nope/'], error: true)['error']);
    }

    public function test_audit_seo_finds_problems(): void
    {
        $this->tool('update_seo', ['path' => '/faq/', 'description' => '']);
        $one = $this->tool('audit_seo', ['path' => '/faq/']);
        $this->assertSame(1, $one['checked']);
        $this->assertContains('missing meta description', $one['pages']['/faq/']['issues']);

        $site = $this->tool('audit_seo');
        $this->assertGreaterThan(40, $site['checked']);
    }

    public function test_schema_read_write_generate(): void
    {
        $blocks = $this->tool('get_schema', ['path' => '/dubai/'])['blocks'];
        $this->assertContains('FAQPage', array_column($blocks, 'type'));

        $faq = $this->tool('generate_schema', ['path' => '/dubai/', 'type' => 'FAQPage'])['json_ld'];
        $this->assertSame('https://schema.org', $faq['@context']);
        $this->assertNotEmpty($faq['mainEntity']);

        $article = $this->tool('generate_schema', ['path' => '/salon-marketing-dubai-guide/', 'type' => 'Article', 'save' => true]);
        $this->assertArrayHasKey('saved', $article);
        $html = $this->get('/salon-marketing-dubai-guide/')->getContent();
        preg_match_all('#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $html, $m);
        $types = array_map(fn ($j) => json_decode($j, true)['@type'] ?? 'graph', $m[1]);
        $this->assertContains('Article', $types);
        foreach ($m[1] as $json) {
            $this->assertNotNull(json_decode($json, true), 'Invalid JSON-LD on the page');
        }

        $this->assertNotEmpty($this->tool('generate_schema', ['type' => 'LocalBusiness'])['json_ld']['address']);
        $this->assertStringContainsString('needs a page', $this->tool('generate_schema', ['type' => 'Service'], error: true)['error']);

        $saved = $this->tool('update_schema', ['path' => '/about-us/', 'json' => ['@type' => 'Organization', 'name' => 'Glowth']])['saved'];
        $this->get('/about-us/')->assertSee('"@type":"Organization","name":"Glowth"', false);
        $this->tool('update_schema', ['path' => '/about-us/', 'block_id' => $saved['id'], 'remove' => true]);
        $this->get('/about-us/')->assertDontSee('"@type":"Organization","name":"Glowth"', false);
        $this->assertStringContainsString('@type', $this->tool('update_schema', ['path' => '/about-us/', 'json' => ['name' => 'x']], error: true)['error']);
    }

    public function test_redirects_and_links(): void
    {
        $this->tool('save_redirect', ['from' => '/old-offer', 'to' => '/get-a-free-audit/', 'status' => 302]);
        $this->get('/old-offer/')->assertStatus(302)->assertRedirect('http://localhost/get-a-free-audit/');
        $listed = collect($this->tool('list_redirects')['redirects'])->firstWhere('from', '/old-offer/');
        $this->assertSame(1, $listed['hits']);
        $this->assertStringContainsString('same address', $this->tool('save_redirect', ['from' => '/a/', 'to' => '/a'], error: true)['error']);
        $this->tool('delete_redirect', ['from' => '/old-offer/']);
        $this->get('/old-offer/')->assertNotFound();

        // Old addresses from the routes are redirects now
        $this->get('/privacy-policy/')->assertStatus(301)->assertRedirect('http://localhost/privacy-policy-2/');

        $links = $this->tool('get_internal_links', ['path' => '/dubai/']);
        $this->assertContains('/get-a-free-audit/', $links['outbound']);
        $this->assertNotEmpty($this->tool('get_internal_links')['pages']);

        $this->tool('add_page_section', ['slug' => 'faq', 'key' => 'broken', 'html' => '<p><a href="/no-such-page/">x</a> <a href="https://example.com/gone">y</a></p>']);
        Http::fake(['example.com/*' => Http::response('', 404)]);
        $broken = $this->tool('check_broken_links', ['path' => '/faq/', 'include_external' => true]);
        $this->assertContains(['on' => '/faq/', 'link' => '/no-such-page/'], $broken['broken_internal']);
        $this->assertSame('https://example.com/gone', $broken['broken_external'][0]['link']);
    }

    public function test_sitemaps_and_robots(): void
    {
        $this->get('/sitemap_index.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>https://glowthdot.com/post-sitemap.xml</loc>', false);
        $this->get('/sitemap.xml')->assertOk();
        $this->assertSame(31, substr_count($this->get('/post-sitemap.xml')->getContent(), '<url>') + 1);

        $check = $this->tool('validate_sitemap_robots');
        $this->assertTrue($check['ok'], implode("\n", $check['problems']));

        $this->tool('update_robots', ['content' => "User-agent: *\nDisallow: /api/\n\nSitemap: https://glowthdot.com/sitemap_index.xml"]);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /api/');
        $this->assertStringContainsString('User-agent', $this->tool('update_robots', ['content' => 'nothing'], error: true)['error']);

        // Publishing a post puts it in the sitemap straight away
        $this->tool('create_blog', ['title' => 'Sitemap Test Post', 'content' => '<p>x</p>', 'status' => 'published']);
        $this->assertStringContainsString('/sitemap-test-post/<', $this->get('/post-sitemap.xml')->getContent());
    }
}
