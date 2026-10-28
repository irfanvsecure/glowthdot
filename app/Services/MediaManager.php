<?php

namespace App\Services;

use App\Mcp\ToolError;
use App\Models\Media;
use App\Models\PageSection;
use App\Models\Post;
use App\Models\SeoMeta;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

/**
 * The image library: uploads (kept as sent, plus a WebP copy at most MAX_WIDTH wide and smaller
 * copies for srcset), where each image is used, and swapping one image for another everywhere.
 */
class MediaManager
{
    public const MAX_WIDTH = 2560;

    public const SIZES = [300, 768, 1024, 1536];

    public const MAX_BYTES = 15 * 1024 * 1024;

    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    /** Bytes of an image from a URL or base64 (with or without a data: prefix) */
    public function fetch(?string $url, ?string $base64): string
    {
        if ($url) {
            if (! preg_match('#^https?://#i', $url)) {
                throw new ToolError('The image URL must start with http:// or https://');
            }
            $host = parse_url($url, PHP_URL_HOST);
            $ip = $host ? gethostbyname($host) : null;
            if (! $ip || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw new ToolError('That address points to a private network; give a public image URL.');
            }
            $response = Http::timeout(20)->withOptions(['stream' => false])->get($url);
            if (! $response->successful()) {
                throw new ToolError('Could not download the image (HTTP '.$response->status().').');
            }
            $bytes = $response->body();
        } elseif ($base64) {
            $bytes = base64_decode(preg_replace('#^data:[^,]+,#', '', $base64), true);
            if ($bytes === false) {
                throw new ToolError('image_base64 is not valid base64.');
            }
        } else {
            throw new ToolError('Give url or image_base64.');
        }
        if (strlen($bytes) > self::MAX_BYTES) {
            throw new ToolError('The image is larger than 15 MB.');
        }

        return $bytes;
    }

    public function store(string $bytes, string $filename, ?string $alt, ?string $title): Media
    {
        $info = @getimagesizefromstring($bytes);
        $mime = $info['mime'] ?? null;
        if (! $info || ! isset(self::TYPES[$mime])) {
            throw new ToolError('That is not a JPG, PNG, WebP or GIF image.');
        }
        $image = @imagecreatefromstring($bytes);
        if (! $image) {
            throw new ToolError('The image could not be read.');
        }

        $base = Slugs::make(pathinfo($filename, PATHINFO_FILENAME)) ?: 'image';
        $dir = '/images/'.now()->format('Y/m');
        @mkdir(public_path($dir), 0755, true);
        $base = $this->freeName($dir, $base);

        // The file as it came
        $original = "$dir/$base.".self::TYPES[$mime];
        file_put_contents(public_path($original), $bytes);

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        [$w, $h] = [imagesx($image), imagesy($image)];
        if ($w > self::MAX_WIDTH) {
            $image = imagescale($image, self::MAX_WIDTH) ?: $image;
            [$w, $h] = [imagesx($image), imagesy($image)];
        }
        $main = "$dir/$base.webp";
        imagewebp($image, public_path($main), 82);

        $sizes = [];
        foreach (self::SIZES as $width) {
            if ($width >= $w) {
                continue;
            }
            $small = imagescale($image, $width);
            $height = imagesy($small);
            $path = "$dir/$base-{$width}x{$height}.webp";
            imagewebp($small, public_path($path), 82);
            $sizes[] = ['path' => $path, 'width' => $width, 'height' => $height];
        }

        return Media::create([
            'path' => $main, 'filename' => basename($main), 'alt' => $alt, 'title' => $title, 'mime' => 'image/webp',
            'width' => $w, 'height' => $h, 'size' => filesize(public_path($main)), 'sizes' => $sizes, 'original_path' => $original,
        ]);
    }

    private function freeName(string $dir, string $base): string
    {
        $name = $base;
        for ($i = 2; is_file(public_path("$dir/$name.webp")) || Media::withTrashed()->where('path', "$dir/$name.webp")->exists(); $i++) {
            $name = "$base-$i";
        }

        return $name;
    }

    /**
     * Where an image is used: page sections, posts (content and featured image), SEO images,
     * settings, and page stylesheets (read only).
     *
     * @return list<array{kind: string, where: string}>
     */
    public function usage(Media $media): array
    {
        $paths = $media->paths();
        $has = fn (?string $text) => $text !== null && array_filter($paths, fn ($p) => str_contains($text, $p));
        $out = [];
        foreach (PageSection::with('page')->get() as $s) {
            if ($s->page && $has($s->html())) {
                $out[] = ['kind' => 'section', 'where' => $s->page->path.'#'.$s->key];
            }
        }
        foreach (Post::all() as $p) {
            if (in_array($p->featured_image, $paths, true)) {
                $out[] = ['kind' => 'featured_image', 'where' => $p->url()];
            }
            if ($has($p->content)) {
                $out[] = ['kind' => 'post', 'where' => $p->url()];
            }
        }
        foreach (SeoMeta::with('seoable')->get() as $m) {
            if ($m->seoable && in_array($m->og_image, $paths, true)) {
                $out[] = ['kind' => 'og_image', 'where' => $m->seoable->url()];
            }
        }
        foreach (Setting::all() as $setting) {
            if ($has(json_encode($setting->value, JSON_UNESCAPED_SLASHES))) {
                $out[] = ['kind' => 'setting', 'where' => $setting->key];
            }
        }
        foreach (array_merge(glob(public_path('css/pages/*.css')), glob(public_path('css/*.css'))) as $css) {
            if ($has(file_get_contents($css))) {
                $out[] = ['kind' => 'stylesheet', 'where' => str_replace(public_path(), '', $css)];
            }
        }
        foreach (glob(resource_path('views/partials/*.blade.php')) as $view) {
            if ($has(file_get_contents($view))) {
                $out[] = ['kind' => 'template', 'where' => 'resources/views/partials/'.basename($view)];
            }
        }

        return $out;
    }

    /**
     * Puts $to wherever $from is used (each size of $from becomes the nearest size of $to).
     * Stylesheets and templates are files and are left alone.
     *
     * @return array{replaced: list<string>}
     */
    public function replace(Media $from, Media $to): array
    {
        $map = [];
        foreach (array_merge([['path' => $from->path, 'width' => $from->width]], $from->sizes ?? []) as $size) {
            $map[$size['path']] = $to->sized((int) $size['width'])['path'];
        }
        if ($from->webp_path) {
            $map[$from->webp_path] = $to->path;
        }
        // Whole srcset values first, so a replaced srcset lists $to's own sizes
        $swap = function (string $html) use ($to, $map) {
            $html = preg_replace_callback('#\ssrcset="([^"]*)"#', fn ($m) => array_filter(array_keys($map), fn ($p) => str_contains($m[1], $p))
                ? ' srcset="'.$to->srcset().'"' : $m[0], $html);

            return strtr($html, $map);
        };
        $done = [];
        foreach (PageSection::with('page')->get() as $s) {
            $new = $swap($s->html());
            if ($new !== $s->html()) {
                $s->saveRevision('before replace_image');
                $s->update(['content' => ['html' => $new] + $s->content]);
                $done[] = $s->page?->path.'#'.$s->key;
            }
        }
        foreach (Post::all() as $p) {
            $changed = false;
            if (isset($map[$p->featured_image]) || $p->featured_image === $from->path) {
                $p->featured_image = $to->path;
                $changed = true;
            }
            $new = $swap($p->content);
            if ($new !== $p->content) {
                $p->content = $new;
                $changed = true;
            }
            if ($changed) {
                $p->save();
                $done[] = $p->url();
            }
        }
        foreach (SeoMeta::with('seoable')->get() as $m) {
            if (isset($map[$m->og_image])) {
                $m->update(['og_image' => $to->path, 'og_image_width' => $to->width, 'og_image_height' => $to->height, 'og_image_type' => $to->mime]);
                $done[] = 'og:image of '.$m->seoable?->url();
            }
        }
        foreach (Setting::all() as $setting) {
            $json = json_encode($setting->value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $new = strtr($json, $map);
            if ($new !== $json) {
                $setting->update(['value' => json_decode($new, true)]);
                $done[] = 'setting '.$setting->key;
            }
        }

        return ['replaced' => $done];
    }

    /** Rename an image's files (keeps the folder) and every reference to them */
    public function rename(Media $media, string $filename): Media
    {
        $base = Slugs::make(pathinfo($filename, PATHINFO_FILENAME));
        if ($base === '') {
            throw new ToolError('Give a file name with letters or numbers.');
        }
        $dir = dirname($media->path);
        $old = pathinfo($media->path, PATHINFO_FILENAME);
        if ($base === $old) {
            return $media;
        }
        if (is_file(public_path("$dir/$base.".pathinfo($media->path, PATHINFO_EXTENSION)))) {
            throw new ToolError("$dir/$base.* exists already.");
        }
        $map = [];
        foreach (array_filter(array_merge($media->paths(), [$media->original_path])) as $path) {
            $new = $dir.'/'.preg_replace('#^'.preg_quote($old, '#').'#', $base, basename($path));
            if (is_file(public_path($path)) && rename(public_path($path), public_path($new))) {
                $map[$path] = $new;
            }
        }
        $copy = $media->replicate();
        $copy->path = $map[$media->path] ?? $media->path;
        $copy->sizes = array_map(fn ($s) => ['path' => $map[$s['path']] ?? $s['path']] + $s, $media->sizes ?? []);
        $copy->webp_path = $media->webp_path ? ($map[$media->webp_path] ?? $media->webp_path) : null;
        $copy->original_path = $media->original_path ? ($map[$media->original_path] ?? $media->original_path) : null;
        $copy->filename = basename($copy->path);

        // References in content, then the record itself
        $this->replaceExact($map);
        $media->update($copy->only(['path', 'sizes', 'webp_path', 'original_path', 'filename']));

        return $media;
    }

    /** Swap exact paths in content (used by rename) */
    private function replaceExact(array $map): void
    {
        foreach (PageSection::all() as $s) {
            $new = strtr($s->html(), $map);
            if ($new !== $s->html()) {
                $s->saveRevision('before image rename');
                $s->update(['content' => ['html' => $new] + $s->content]);
            }
        }
        foreach (Post::all() as $p) {
            $p->content = strtr($p->content, $map);
            $p->featured_image = $map[$p->featured_image] ?? $p->featured_image;
            if ($p->isDirty()) {
                $p->save();
            }
        }
        foreach (SeoMeta::all() as $m) {
            if (isset($map[$m->og_image])) {
                $m->update(['og_image' => $map[$m->og_image]]);
            }
        }
    }

    /** Deletes the files of an image (purge) */
    public function deleteFiles(Media $media): void
    {
        foreach (array_filter(array_merge($media->paths(), [$media->original_path])) as $path) {
            if (str_starts_with($path, '/images/') && is_file(public_path($path))) {
                @unlink(public_path($path));
            }
        }
    }
}
