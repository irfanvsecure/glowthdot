<?php

namespace App\Mcp\Tools;

use App\Mcp\SiteTool;
use App\Mcp\ToolError;
use App\Models\Media;
use App\Models\PageSection;
use App\Models\Post;
use App\Services\Activity;
use App\Services\MediaManager;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class MediaTools
{
    /** @return list<SiteTool> */
    public static function tools(): array
    {
        $media = fn () => app(MediaManager::class);

        return [
            new SiteTool('list_images', 'The image library: path, size, alt text, resized copies and where each image is used (pages, posts, SEO, settings, stylesheets). Filter by a word in the file name or alt text, or only images without alt text or not used anywhere.',
                fn (JsonSchema $s) => [
                    'search' => $s->string(),
                    'missing_alt' => $s->boolean(),
                    'unused' => $s->boolean(),
                    'with_usage' => $s->boolean()->description('Default true'),
                ],
                ['search' => 'nullable|string|max:100', 'missing_alt' => 'nullable|boolean', 'unused' => 'nullable|boolean', 'with_usage' => 'nullable|boolean'],
                function (array $in) use ($media) {
                    $items = Media::query()
                        ->when($in['search'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('path', 'like', "%$v%")->orWhere('alt', 'like', "%$v%")))
                        ->when($in['missing_alt'] ?? false, fn ($q) => $q->where(fn ($w) => $w->whereNull('alt')->orWhere('alt', '')))
                        ->orderByDesc('id')->get();
                    $usage = ($in['with_usage'] ?? true) || ($in['unused'] ?? false);
                    $out = [];
                    foreach ($items as $m) {
                        $row = self::summary($m);
                        if ($usage) {
                            $row['used_in'] = $media()->usage($m);
                            if (($in['unused'] ?? false) && $row['used_in']) {
                                continue;
                            }
                        }
                        $out[] = $row;
                    }

                    return ['images' => $out, 'total' => count($out)];
                }),

            new SiteTool('upload_image', 'Add an image from a public URL or base64 data. The file is kept as sent, and a WebP copy (at most 2560 px wide) plus 300/768/1024/1536 px copies for srcset are made. Returns the path to use in HTML, posts and SEO.',
                fn (JsonSchema $s) => [
                    'url' => $s->string(),
                    'image_base64' => $s->string(),
                    'filename' => $s->string()->required()->description('e.g. salon-seo-dubai.jpg; becomes the file name'),
                    'alt' => $s->string()->required()->description('What the image shows, for screen readers and search'),
                    'title' => $s->string(),
                ],
                ['url' => 'nullable|url|required_without:image_base64', 'image_base64' => 'nullable|string|required_without:url',
                    'filename' => 'required|string|max:150', 'alt' => 'required|string|max:300', 'title' => 'nullable|string|max:200'],
                function (array $in) use ($media) {
                    $item = $media()->store($media()->fetch($in['url'] ?? null, $in['image_base64'] ?? null), $in['filename'], $in['alt'], $in['title'] ?? null);
                    Activity::log('image.upload', $item, null, $item->only(['path', 'alt', 'width', 'height']));

                    return ['uploaded' => self::summary($item), 'html' => self::html($item)];
                }, writes: true),

            new SiteTool('update_image', 'Change an image\'s alt text or title, or rename its files (every page, post and SEO tag that uses it is updated).',
                fn (JsonSchema $s) => self::ref($s) + ['alt' => $s->string(), 'title' => $s->string(), 'filename' => $s->string()->description('New file name, without folder')],
                self::refRules() + ['alt' => 'nullable|string|max:300', 'title' => 'nullable|string|max:200', 'filename' => 'nullable|string|max:150'],
                function (array $in) use ($media) {
                    $item = self::find($in);
                    $before = $item->only(['path', 'alt', 'title']);
                    if (isset($in['filename'])) {
                        $media()->rename($item, $in['filename']);
                    }
                    $item->fill(array_filter(['alt' => $in['alt'] ?? null, 'title' => $in['title'] ?? null], fn ($v) => $v !== null))->save();
                    // The alt text in the pages follows
                    $alt = isset($in['alt']) ? self::updateAltInContent($item) : 0;
                    Activity::log('image.update', $item, $before, $item->only(array_keys($before)));

                    return ['updated' => self::summary($item), 'alt_updated_in' => $alt];
                }, writes: true),

            new SiteTool('replace_image', 'Swap an image for another everywhere it is used (page sections, posts, featured images, SEO images, settings). Sizes are matched, srcset values are rebuilt. Stylesheet backgrounds are files and are not changed (they are listed).',
                fn (JsonSchema $s) => ['from' => $s->string()->required()->description('Path or id of the image to replace'), 'to' => $s->string()->required()->description('Path or id of the new image')],
                ['from' => 'required|string', 'to' => 'required|string'],
                function (array $in) use ($media) {
                    $from = self::find(['image' => $in['from']]);
                    $to = self::find(['image' => $in['to']]);
                    if ($from->is($to)) {
                        throw new ToolError('from and to are the same image.');
                    }
                    $result = $media()->replace($from, $to);
                    $files = array_values(array_filter($media()->usage($from), fn ($u) => in_array($u['kind'], ['stylesheet', 'template'], true)));
                    Activity::log('image.replace', $from, ['path' => $from->path], ['path' => $to->path, 'in' => $result['replaced']]);

                    return $result + ($files ? ['not_changed_files' => $files] : []);
                }, writes: true),

            new SiteTool('delete_image', 'Move an image to the trash. Refused while the image is used somewhere, unless force: true. Its files stay until purge_item.',
                fn (JsonSchema $s) => self::ref($s) + ['force' => $s->boolean()],
                self::refRules() + ['force' => 'nullable|boolean'],
                function (array $in) use ($media) {
                    $item = self::find($in);
                    $usage = $media()->usage($item);
                    if ($usage && empty($in['force'])) {
                        throw new ToolError('This image is in use: '.implode(', ', array_map(fn ($u) => $u['kind'].' '.$u['where'], $usage)).'. Replace it first (replace_image) or pass force: true.');
                    }
                    $item->delete();
                    Activity::log('image.delete', $item, $item->only(['path', 'alt']), ['was_used_in' => $usage]);

                    return ['deleted' => self::summary($item), 'was_used_in' => $usage];
                }, writes: true),
        ];
    }

    private static function ref(JsonSchema $s): array
    {
        return ['id' => $s->integer(), 'image' => $s->string()->description('Path of the image, e.g. /images/2026/10/photo.webp, or of one of its sizes')];
    }

    private static function refRules(): array
    {
        return ['id' => 'nullable|integer', 'image' => 'nullable|string'];
    }

    public static function find(array $in): Media
    {
        $ref = $in['id'] ?? $in['image'] ?? throw new ToolError('Say which image: id or image (its path).');
        if (is_numeric($ref)) {
            return Media::find((int) $ref) ?? throw new ToolError("No image with id $ref.");
        }
        $path = '/'.ltrim(parse_url($ref, PHP_URL_PATH) ?: $ref, '/');
        $found = Media::where('path', $path)->orWhere('webp_path', $path)->orWhere('original_path', $path)->first()
            ?? Media::all()->first(fn ($m) => in_array($path, $m->paths(), true));

        return $found ?? throw new ToolError("No image at $path in the library. list_images shows them.");
    }

    private static function summary(Media $m): array
    {
        return ['id' => $m->id, 'path' => $m->path, 'alt' => $m->alt, 'title' => $m->title, 'width' => $m->width, 'height' => $m->height,
            'kb' => $m->size ? round($m->size / 1024) : null, 'mime' => $m->mime, 'sizes' => array_column($m->sizes ?? [], 'path'),
            'webp' => $m->webp_path, 'original' => $m->original_path];
    }

    private static function html(Media $m): string
    {
        return '<img src="'.e($m->path).'" width="'.$m->width.'" height="'.$m->height.'" alt="'.e($m->alt).'" loading="lazy" decoding="async"'
            .($m->sizes ? ' srcset="'.e($m->srcset()).'" sizes="(max-width: '.$m->width.'px) 100vw, '.$m->width.'px"' : '').' />';
    }

    /** alt="..." of every <img> of this image in sections and posts */
    private static function updateAltInContent(Media $m): int
    {
        $paths = array_map(fn ($p) => preg_quote($p, '#'), $m->paths());
        $re = '#(<img\b[^>]*\bsrc="(?:'.implode('|', $paths).')"[^>]*\balt=")[^"]*(")#';
        $re2 = '#(<img\b[^>]*\balt=")[^"]*("[^>]*\bsrc="(?:'.implode('|', $paths).')")#';
        $n = 0;
        $alt = e($m->alt);
        foreach (PageSection::all() as $s) {
            $new = preg_replace([$re, $re2], '${1}'.$alt.'${2}', $s->html(), -1, $c);
            if ($c && $new !== $s->html()) {
                $s->saveRevision('before alt text change');
                $s->update(['content' => ['html' => $new] + $s->content]);
                $n += $c;
            }
        }
        foreach (Post::all() as $p) {
            $new = preg_replace([$re, $re2], '${1}'.$alt.'${2}', $p->content, -1, $c);
            if ($c && $new !== $p->content) {
                $p->update(['content' => $new]);
                $n += $c;
            }
        }

        return $n;
    }
}
