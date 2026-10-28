<?php

namespace Tests\Feature\Mcp;

use App\Models\Media;
use Tests\Concerns\CallsMcp;
use Tests\TestCase;

class MediaToolsTest extends TestCase
{
    use CallsMcp;

    /** @var list<string> files the uploads wrote into public/images */
    private array $written = [];

    protected function tearDown(): void
    {
        foreach (Media::withTrashed()->where('filename', 'like', 'mcp-test-%')->get() as $m) {
            foreach (array_filter(array_merge($m->paths(), [$m->original_path])) as $p) {
                @unlink(public_path($p));
            }
        }
        parent::tearDown();
    }

    private function png(int $w = 1800, int $h = 900): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 46, 126));
        ob_start();
        imagepng($im);

        return base64_encode(ob_get_clean());
    }

    public function test_upload_makes_webp_and_srcset_sizes_and_keeps_the_original(): void
    {
        $up = $this->tool('upload_image', ['image_base64' => 'data:image/png;base64,'.$this->png(), 'filename' => 'mcp-test-cover.png', 'alt' => 'Pink test cover']);
        $m = $up['uploaded'];
        $this->assertStringEndsWith('/mcp-test-cover.webp', $m['path']);
        $this->assertSame('image/webp', $m['mime']);
        $this->assertSame([1800, 900], [$m['width'], $m['height']]);
        $this->assertCount(4, $m['sizes']);
        $this->assertStringEndsWith('-300x150.webp', $m['sizes'][0]);
        $this->assertFileExists(public_path($m['path']));
        $this->assertFileExists(public_path($m['original']));
        $this->assertStringContainsString('srcset="', $up['html']);

        $this->assertStringContainsString('not a JPG, PNG', $this->tool('upload_image', ['image_base64' => base64_encode('not an image'), 'filename' => 'mcp-test-x.png', 'alt' => 'x'], error: true)['error']);
        $this->assertStringContainsString('private network', $this->tool('upload_image', ['url' => 'http://127.0.0.1/x.png', 'filename' => 'mcp-test-y.png', 'alt' => 'x'], error: true)['error']);
    }

    public function test_usage_alt_text_replace_delete_and_purge(): void
    {
        $up = $this->tool('upload_image', ['image_base64' => $this->png(), 'filename' => 'mcp-test-a.png', 'alt' => 'First'])['uploaded'];
        $other = $this->tool('upload_image', ['image_base64' => $this->png(1200, 600), 'filename' => 'mcp-test-b.png', 'alt' => 'Second'])['uploaded'];

        $this->tool('add_page_section', ['slug' => 'about-us', 'key' => 'img-test', 'html' => '<div><img src="'.$up['path'].'" alt="First" width="1800" height="900"></div>']);
        $used = collect($this->tool('list_images', ['search' => 'mcp-test-a'])['images'])->first()['used_in'];
        $this->assertSame([['kind' => 'section', 'where' => '/about-us/#img-test']], $used);

        // Alt text follows into the pages
        $this->tool('update_image', ['image' => $up['path'], 'alt' => 'Pink salon cover']);
        $this->get('/about-us/')->assertSee('alt="Pink salon cover"', false);

        // In use: delete is refused, replace swaps it everywhere
        $this->assertStringContainsString('in use', $this->tool('delete_image', ['image' => $up['path']], error: true)['error']);
        $replaced = $this->tool('replace_image', ['from' => $up['path'], 'to' => $other['path']]);
        $this->assertSame(['/about-us/#img-test'], $replaced['replaced']);
        $this->get('/about-us/')->assertSee($other['path'], false)->assertDontSee($up['path'], false);

        $this->tool('delete_image', ['image' => $up['path']]);
        $this->assertSoftDeleted('media', ['id' => $up['id']]);
        $this->assertFileExists(public_path($up['path']));
        $this->assertStringContainsString('confirm: true', $this->tool('purge_item', ['type' => 'image', 'id' => $up['id'], 'confirm' => false], error: true)['error']);
        $this->tool('purge_item', ['type' => 'image', 'id' => $up['id'], 'confirm' => true]);
        $this->assertFileDoesNotExist(public_path($up['path']));
        $this->assertDatabaseMissing('media', ['id' => $up['id']]);

        // force deletes an image that is in use
        $this->tool('delete_image', ['image' => $other['path'], 'force' => true]);
        $this->assertSoftDeleted('media', ['id' => $other['id']]);
    }

    public function test_rename_updates_the_files_and_the_pages(): void
    {
        $up = $this->tool('upload_image', ['image_base64' => $this->png(), 'filename' => 'mcp-test-old.png', 'alt' => 'x'])['uploaded'];
        $this->tool('add_page_section', ['slug' => 'faq', 'key' => 'img-test', 'html' => '<p><img src="'.$up['path'].'" alt="x"></p>']);

        $renamed = $this->tool('update_image', ['id' => $up['id'], 'filename' => 'mcp-test-new'])['updated'];
        $this->assertStringEndsWith('/mcp-test-new.webp', $renamed['path']);
        $this->assertFileExists(public_path($renamed['path']));
        $this->assertFileDoesNotExist(public_path($up['path']));
        $this->get('/faq/')->assertSee($renamed['path'], false);
    }

    public function test_existing_images_are_in_the_library_with_their_usage(): void
    {
        $images = $this->tool('list_images', ['search' => 'salon-marketing-calendar-dubai-cover'])['images'];
        $this->assertNotEmpty($images);
        $this->assertContains('featured_image', array_column($images[0]['used_in'], 'kind'));
    }
}
