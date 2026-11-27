<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Page;
use App\Models\SeoMeta;
use App\Support\ContentVersion;

/**
 * Share images (og:image) for pages that have no picture of their own: a 1200x630 card with the
 * page's title in the site's type and colours. Cards live in /images/og/ and are named after the
 * page and a hash of what is on them, so a new title gives a new address and social sites refetch.
 */
class OgCards
{
    public const DIR = '/images/og';

    private const W = 1200;

    private const H = 630;

    /** Kind of page, from the header menu and the industries page */
    private ?array $kinds = null;

    /**
     * Make (or remake) the card of every page that needs one.
     *
     * @param  bool  $force  also pages that have an image of their own
     * @return array<string, string> path => card
     */
    public function refreshAll(bool $force = false): array
    {
        $done = [];
        // decided before any change: once the others move off a shared image, the last one would look unshared
        $models = Page::with('seo')->get()->concat(Category::with('seo')->get())
            ->filter(fn ($model) => $force || $this->needsCard($model));
        foreach ($models as $model) {
            $done[$this->path($model)] = $this->make($model);
        }
        if ($done) {
            ContentVersion::bump();
        }

        return $done;
    }

    /** No image, an image shared with another page, or an earlier card of ours */
    public function needsCard(Page|Category $model): bool
    {
        $image = $model->seo?->og_image;
        if (! $image || str_starts_with($image, self::DIR.'/')) {
            return true;
        }

        return SeoMeta::where('og_image', $image)->where('id', '!=', $model->seo->id)->exists();
    }

    /** After a title or slug change: remake the card if the page uses one of ours */
    public function refreshIfCard(Page|Category $model): void
    {
        $model->loadMissing('seo');
        if (! $model->seo?->og_image || str_starts_with($model->seo->og_image, self::DIR.'/')) {
            $this->make($model);
        }
    }

    /** Draw the card, store it on the page's SEO and return its path */
    public function make(Page|Category $model): string
    {
        $title = $this->title($model);
        $label = $this->label($model);
        $base = $model instanceof Category ? 'category-'.$model->slug : ($model->path === '/' ? 'home' : $model->slug);
        $name = $base.'-'.substr(md5($title.'|'.$label.'|v3'), 0, 8).'.png';
        $path = self::DIR.'/'.$name;

        if (! is_file(public_path($path))) {
            @mkdir(public_path(self::DIR), 0755, true);
            imagepng($this->draw($title, $label, crc32($base)), public_path($path), 9);
        }

        $url = $this->path($model);
        $seo = $model->seo ?? $model->seo()->create(['canonical' => $url, 'og_url' => $url]);
        $old = $seo->og_image;
        $seo->update(['og_image' => $path, 'og_image_width' => self::W, 'og_image_height' => self::H, 'og_image_type' => 'image/png']);
        $model->setRelation('seo', $seo);

        // the card it replaces, unless another page still points at it
        if ($old && $old !== $path && str_starts_with($old, self::DIR.'/') && ! SeoMeta::where('og_image', $old)->exists()) {
            @unlink(public_path($old));
        }

        return $path;
    }

    private function path(Page|Category $model): string
    {
        return $model instanceof Category ? $model->url() : $model->path;
    }

    /** The page title without the " | Glowth" ending; a category's name */
    public function title(Page|Category $page): string
    {
        if ($page instanceof Category) {
            return $page->name.' guides & articles';
        }
        $site = preg_quote(setting('site_name', 'Glowth'), '/');
        $title = preg_replace('/\s*[|\-–—]\s*'.$site.'\s*$/u', '', trim($page->title)) ?: $page->title;

        return $page->path === '/' ? 'Digital marketing for salons & clinics in the UAE' : $title;
    }

    /** "Service", "Industry", "Location" or a general line */
    public function label(Page|Category $page): string
    {
        if ($page instanceof Category) {
            return 'Glowth blog';
        }

        return $this->kinds()[$page->path] ?? 'Beauty & clinic marketing · UAE';
    }

    private function kinds(): array
    {
        if ($this->kinds !== null) {
            return $this->kinds;
        }
        $kinds = [];
        foreach ((array) setting('nav_menu', []) as $item) {
            $kind = match (true) {
                ($item['url'] ?? '') === '/services/' => 'Service',
                stripos($item['label'] ?? '', 'location') === 0 => 'Location',
                default => null,
            };
            foreach ($kind ? ($item['children'] ?? []) : [] as $child) {
                $kinds[$child['url']] = $kind;
            }
        }
        // the pages the industries page links to
        $industries = Page::where('path', '/industries/')->first();
        foreach ($industries?->sections ?? [] as $section) {
            preg_match_all('#href=["\'](?:https?://[^/"\']+)?(/[a-z0-9-]+/)["\']#', (string) ($section->content['html'] ?? ''), $m);
            foreach ($m[1] as $url) {
                $kinds[$url] ??= 'Industry';
            }
        }

        return $this->kinds = $kinds;
    }

    /** @return \GdImage */
    private function draw(string $title, string $label, int $seed)
    {
        // two soft glows, violet and pink, placed a little differently on every page; drawn at a
        // quarter of the size and scaled up, which is quicker and smoother
        $q = 4;
        $bg = imagecreatetruecolor(self::W / $q, (int) ceil(self::H / $q));
        imagealphablending($bg, true);
        imagefill($bg, 0, 0, imagecolorallocate($bg, 0x0E, 0x0D, 0x12));
        $this->glow($bg, intdiv(760 + ($seed % 300), $q), intdiv(40 + ($seed >> 4) % 120, $q), intdiv(620, $q), [0x7C, 0x3A, 0xED]);
        $this->glow($bg, intdiv(1240 - ($seed >> 8) % 260, $q), intdiv(640, $q), intdiv(460, $q), [0xFF, 0x2E, 0x7E]);

        $im = imagecreatetruecolor(self::W, self::H);
        imagecopyresampled($im, $bg, 0, 0, 0, 0, self::W, self::H, imagesx($bg), imagesy($bg));
        imagealphablending($im, true);

        $bold = resource_path('fonts/SpaceGrotesk-Bold.ttf');
        $text = resource_path('fonts/Inter-Medium.ttf');
        $white = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);
        $muted = imagecolorallocate($im, 0xB9, 0xB4, 0xC6);
        $pink = imagecolorallocate($im, 0xFF, 0x7E, 0xB0);

        // label in a pill
        $label = mb_strtoupper($label);
        $box = imagettfbbox(17, 0, $text, $label);
        $lw = $box[2] - $box[0];
        $this->pill($im, 80, 84, $lw + 44, 44, imagecolorallocate($im, 0x3F, 0x14, 0x28));
        imagettftext($im, 17, 0, 102, 113, $pink, $text, $label);

        // the title: as large as fits in three lines
        $lines = [$title];
        $size = 50;
        foreach ([78, 70, 62, 56, 50] as $size) {
            $lines = $this->wrap($title, $bold, $size, 1000);
            if (count($lines) <= 3) {
                break;
            }
        }
        $lines = array_slice($lines, 0, 3);
        $lineHeight = (int) round($size * 1.16);
        $y = 190 + $size + (int) ((3 - count($lines)) * $lineHeight / 2.5);
        foreach ($lines as $line) {
            imagettftext($im, $size, 0, 78, $y, $white, $bold, $line);
            $y += $lineHeight;
        }

        // footer: logo and address
        $logo = public_path('images/2026/07/glowth-logo-white-e1785237106441-300x80.png');
        if (is_file($logo) && ($l = @imagecreatefrompng($logo))) {
            $h = 50;
            $w = (int) round(imagesx($l) * $h / imagesy($l));
            imagecopyresampled($im, $l, 80, 528, 0, 0, $w, $h, imagesx($l), imagesy($l));
        }
        $site = parse_url(config('app.canonical_url') ?: 'https://glowthdot.com', PHP_URL_HOST) ?: 'glowthdot.com';
        $box = imagettfbbox(20, 0, $text, $site);
        imagettftext($im, 20, 0, self::W - 80 - ($box[2] - $box[0]), 563, $muted, $text, $site);

        // a thin violet-to-pink line along the bottom
        for ($i = 0; $i < self::W; $i++) {
            $t = $i / self::W;
            $c = imagecolorallocate($im, (int) (0x7C + (0xFF - 0x7C) * $t), (int) (0x3A + (0x2E - 0x3A) * $t), (int) (0xED + (0x7E - 0xED) * $t));
            imageline($im, $i, self::H - 8, $i, self::H - 1, $c);
        }

        return $im;
    }

    /** A radial glow: many faint circles, more opaque towards the middle */
    private function glow($im, int $cx, int $cy, int $r, array $rgb): void
    {
        for ($i = $r; $i > 0; $i -= 2) {
            $alpha = 127 - (int) round(12 * (1 - $i / $r) ** 2);
            imagefilledellipse($im, $cx, $cy, $i * 2, $i * 2, imagecolorallocatealpha($im, $rgb[0], $rgb[1], $rgb[2], max(0, $alpha)));
        }
    }

    private function pill($im, int $x, int $y, int $w, int $h, int $color): void
    {
        $r = intdiv($h, 2);
        imagefilledrectangle($im, $x + $r, $y, $x + $w - $r, $y + $h, $color);
        imagefilledarc($im, $x + $r, $y + $r, $h, $h, 90, 270, $color, IMG_ARC_PIE);
        imagefilledarc($im, $x + $w - $r, $y + $r, $h, $h, 270, 90, $color, IMG_ARC_PIE);
    }

    /** @return list<string> */
    private function wrap(string $text, string $font, int $size, int $max): array
    {
        $lines = [];
        $line = '';
        foreach (preg_split('/\s+/u', $text) as $word) {
            $try = $line === '' ? $word : "$line $word";
            $box = imagettfbbox($size, 0, $font, $try);
            if ($max < $box[2] - $box[0] && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $try;
            }
        }
        $lines[] = $line;

        return $lines;
    }
}
