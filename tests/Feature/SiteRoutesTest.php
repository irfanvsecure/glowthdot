<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SiteRoutesTest extends TestCase
{
    public static function oldUrls(): array
    {
        return [
            ['/our-services/', '/services/'],
            ['/free-audit', '/get-a-free-audit/'],
            ['/privacy-policy/', '/privacy-policy-2/'],
            ['/author/abu-bakar/', '/about-us/'],
        ];
    }

    /** @dataProvider oldUrls */
    public function test_old_urls_redirect_to_the_slash_url(string $from, string $to): void
    {
        $this->get($from)->assertStatus(301)->assertRedirect('http://localhost'.$to);
    }

    /** Production runs `php artisan route:cache`, which once broke closure redirects (HTTP 500). */
    public function test_old_urls_still_redirect_with_cached_routes(): void
    {
        Artisan::call('route:cache');

        try {
            $this->refreshApplication();
            $this->get('/our-services/')->assertStatus(301)->assertRedirect('http://localhost/services/');
            $this->get('/about-us/')->assertOk();
        } finally {
            Artisan::call('route:clear');
        }
    }

    public function test_public_prefix_redirects_to_the_real_address(): void
    {
        // The domain serves the project folder and the root .htaccess routes into public/
        $server = [
            'SCRIPT_NAME' => '/public/index.php',
            'SCRIPT_FILENAME' => public_path('index.php'),
            'PHP_SELF' => '/public/index.php',
        ];

        // Straight through the kernel: the test client rewrites URIs (drops trailing slashes)
        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $location = fn (string $uri) => tap(
            $kernel->handle(\Illuminate\Http\Request::create($uri, 'GET', [], [], [], $server)),
            fn ($r) => $this->assertSame(301, $r->getStatusCode(), $uri)
        )->headers->get('Location');

        $this->assertSame('http://localhost/about-us/', $location('/public/about-us/'));
        $this->assertSame('http://localhost/', $location('/public/index.php'));
        $this->assertSame('http://localhost/faq/?a=1', $location('/public/faq/?a=1'));
    }

    public function test_pages_render_without_cookies(): void
    {
        $this->get('/get-a-free-audit/')->assertOk()->assertHeaderMissing('Set-Cookie');
        $this->get('/no-such-page/')->assertNotFound();
    }
}
