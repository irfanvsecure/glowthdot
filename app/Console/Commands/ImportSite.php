<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Setting;
use App\Support\ContentVersion;
use App\Support\HtmlBlocks;
use App\Support\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * One-time: moves the site's content from the page files in database/import/ into the database,
 * exactly as it is (nothing rewritten). Sections keep their markup byte for byte.
 */
class ImportSite extends Command
{
    protected $signature = 'site:import {--fresh : Empty the content tables first (destroys edits made since the last import)}';

    protected $description = 'Import the pages, posts, images, SEO and settings from database/import into the database';

    private string $dir;

    /** @var array<string, array> parsed view files by slug */
    private array $views = [];

    /** Old URLs that the routes used to redirect */
    private const REDIRECTS = [
        '/privacy-policy/' => '/privacy-policy-2/',
        '/free-audit/' => '/get-a-free-audit/',
        '/our-services/' => '/services/',
        '/author/abu-bakar/' => '/about-us/',
    ];

    public function handle(): int
    {
        $this->dir = database_path('import');

        if (Page::withTrashed()->exists() && ! $this->option('fresh')) {
            $this->error('The site is already imported. Pass --fresh to start over (edits made since are lost).');

            return self::FAILURE;
        }

        foreach (glob($this->dir.'/pages/*.blade.php') as $file) {
            $this->views[basename($file, '.blade.php')] = $this->parseView(file_get_contents($file));
        }

        DB::transaction(function () {
            if ($this->option('fresh')) {
                $this->truncate();
            }
            $this->importSettings();
            $this->importMedia();
            $categories = $this->importCategories();
            $this->importPosts($categories);
            $this->importPages();
            foreach (self::REDIRECTS as $from => $to) {
                Redirect::create(['from_path' => $from, 'to_path' => $to, 'status_code' => 301]);
            }
        });

        ContentVersion::bump();
        $this->info(sprintf('Imported %d pages (%d sections), %d posts, %d categories, %d images, %d settings, %d redirects.',
            Page::count(), PageSection::count(), Post::count(), Category::count(), Media::count(), Setting::count(), Redirect::count()));

        return self::SUCCESS;
    }

    private function truncate(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['category_post', 'post_tag', 'section_revisions', 'page_sections', 'pages', 'posts', 'categories', 'tags',
            'media', 'seo_meta', 'schema_blocks', 'redirects', 'settings', 'activity_log'] as $table) {
            DB::table($table)->delete();
        }
        Schema::enableForeignKeyConstraints();
    }

    // ---------------------------------------------------------------- view files

    private function parseView(string $s): array
    {
        $section = function (string $name) use ($s): ?string {
            return preg_match("#@section\('".$name."'\)\n(.*?)\n@endsection#s", $s, $m) ? $m[1] : null;
        };
        $assets = function (?string $block): array {
            preg_match_all("#versioned\('([^']+)'\)#", (string) $block, $m);

            return $m[1];
        };

        preg_match("#@section\('body_class', '([^']*)'\)#", $s, $body);
        $contentStart = strpos($s, "@section('content')\n");
        $contentEnd = strpos($s, "\n@endsection", $contentStart);
        $content = substr($s, $contentStart + 20, $contentEnd - $contentStart - 20);

        return [
            'head' => $section('head') ?? '',
            'body_class' => $body[1] ?? '',
            'content' => $this->unblade($content),
            'assets' => array_filter([
                'styles' => $assets($section('styles')),
                'theme_styles' => $assets($section('theme_styles')),
                'late_styles' => $assets($section('late_styles')),
                'scripts' => $assets($section('scripts')),
                'theme_scripts' => $assets($section('theme_scripts')),
            ]),
        ];
    }

    /** The page files wrap JSON-LD in @verbatim; the database holds the plain HTML */
    private function unblade(string $html): string
    {
        $html = str_replace(["@verbatim\n<script", "</script>\n@endverbatim"], ['<script', '</script>'], $html);
        if (preg_match('#\{\{|\{!!|@verbatim|@endverbatim#', $html)) {
            throw new RuntimeException('Blade syntax left in page content');
        }

        return $html;
    }

    /** Title, meta, link and JSON-LD tags of a page's head */
    private function parseHead(string $head): array
    {
        $seo = [];
        $schemas = [];
        $attr = fn (string $tag, string $name) => preg_match('#\b'.$name.'=(["\'])(.*?)\1#s', $tag, $m) ? html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5) : null;

        if (preg_match('#<title>(.*?)</title>#s', $head, $m)) {
            $seo['title'] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
        }
        $map = ['description' => 'description', 'og:type' => 'og_type', 'og:title' => 'og_title', 'og:description' => 'og_description',
            'og:url' => 'og_url', 'og:image' => 'og_image', 'og:image:width' => 'og_image_width', 'og:image:height' => 'og_image_height',
            'og:image:type' => 'og_image_type', 'article:published_time' => 'published_time', 'article:modified_time' => 'modified_time',
            'author' => 'author', 'twitter:card' => 'twitter_card', 'robots' => 'robots'];
        preg_match_all('#<meta\b[^>]*>#', $head, $metas);
        foreach ($metas[0] as $tag) {
            $name = $attr($tag, 'name') ?? $attr($tag, 'property');
            if (! isset($map[$name])) {
                throw new RuntimeException("Unknown head tag: $tag");
            }
            $seo[$map[$name]] = $attr($tag, 'content');
        }
        preg_match_all('#<link\b[^>]*>#', $head, $links);
        foreach ($links[0] as $tag) {
            $rel = $attr($tag, 'rel');
            if (! in_array($rel, ['canonical', 'prev', 'next'], true)) {
                throw new RuntimeException("Unknown head tag: $tag");
            }
            $seo[$rel] = $attr($tag, 'href');
        }
        preg_match_all('#<script type="application/ld\+json"(?: id="([^"]+)")?>(.*?)</script>#s', $head, $blocks, PREG_SET_ORDER);
        foreach ($blocks as $i => $b) {
            $data = json_decode($b[2], true);
            $schemas[] = ['type' => $data['@type'] ?? (isset($data['@graph']) ? 'graph' : 'unknown'), 'element_id' => $b[1] ?: null,
                'json' => $b[2], 'sort_order' => $i];
        }

        return [$seo, $schemas];
    }

    private function attachSeo($model, string $head): void
    {
        [$seo, $schemas] = $this->parseHead($head);
        $model->seo()->create($seo);
        foreach ($schemas as $schema) {
            $model->schemaBlocks()->create($schema);
        }
    }

    // ---------------------------------------------------------------- settings, media

    private function importSettings(): void
    {
        $values = json_decode(file_get_contents($this->dir.'/settings.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($values as $key => $value) {
            Setting::create(['key' => $key, 'value' => $value]);
        }
        Settings::forget();
    }

    /** Every image the pages, header, footer and stylesheets use, with its resized copies */
    private function importMedia(): void
    {
        $html = implode("\n", array_column($this->views, 'content')).implode("\n", array_column($this->views, 'head'))
            .implode("\n", array_map('file_get_contents', glob(resource_path('views/partials/*.blade.php'))));
        $css = implode("\n", array_map('file_get_contents', glob(public_path('css/pages/*.css'))));

        $groups = []; // original path => [sizes, alt, width, height]
        preg_match_all('#<img\b[^>]*>#', $html, $imgs);
        foreach ($imgs[0] as $img) {
            $src = preg_match('#\ssrc="([^"]+)"#', $img, $m) ? $m[1] : null;
            if (! $src || ! str_starts_with($src, '/images/')) {
                continue;
            }
            $alt = preg_match('#\salt="([^"]*)"#', $img, $m) ? html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5) : null;
            $set = [];
            if (preg_match('#\ssrcset="([^"]+)"#', $img, $m)) {
                foreach (explode(',', $m[1]) as $part) {
                    [$path, $w] = preg_split('/\s+/', trim($part)) + [null, null];
                    $set[] = ['path' => $path, 'width' => (int) $w];
                }
            }
            $set = $set ?: [['path' => $src, 'width' => preg_match('#\swidth="(\d+)"#', $img, $m) ? (int) $m[1] : null]];
            $original = $this->originalOf($set);
            $groups[$original['path']] ??= ['set' => [], 'alt' => $alt];
            foreach ($set as $s) {
                $groups[$original['path']]['set'][$s['path']] = $s;
            }
            $groups[$original['path']]['alt'] ??= $alt;
        }
        preg_match_all('#(/images/[^"\'\s)&]+\.(?:png|jpe?g|webp|gif|svg))#i', $html.$css, $refs);
        foreach (array_unique($refs[1]) as $path) {
            $known = false;
            foreach ($groups as $g) {
                if (isset($g['set'][$path])) {
                    $known = true;
                    break;
                }
            }
            if (! $known && is_file(public_path($path))) {
                $groups[$path] ??= ['set' => [$path => ['path' => $path, 'width' => null]], 'alt' => null];
            }
        }

        $groups = $this->mergeGroups($groups);

        foreach ($groups as $path => $g) {
            $file = public_path($path);
            $info = is_file($file) ? @getimagesize($file) : false;
            $sizes = [];
            foreach ($g['set'] as $s) {
                if ($s['path'] === $path) {
                    continue;
                }
                $dim = is_file(public_path($s['path'])) ? @getimagesize(public_path($s['path'])) : false;
                $sizes[] = ['path' => $s['path'], 'width' => $s['width'] ?: ($dim[0] ?? null), 'height' => $dim[1] ?? null];
            }
            Media::create([
                'path' => $path,
                'filename' => basename($path),
                'alt' => $g['alt'],
                'mime' => $info['mime'] ?? (str_ends_with($path, '.svg') ? 'image/svg+xml' : null),
                'width' => $info[0] ?? ($g['set'][$path]['width'] ?? null),
                'height' => $info[1] ?? null,
                'size' => is_file($file) ? filesize($file) : null,
                'sizes' => $sizes,
                'webp_path' => is_file($file.'.webp') ? $path.'.webp' : null,
            ]);
        }
    }

    /** One group per image: srcsets that share a file are the same image in other sizes */
    private function mergeGroups(array $groups): array
    {
        $merged = [];
        foreach ($groups as $g) {
            foreach ($merged as $i => $m) {
                if (array_intersect_key($m['set'], $g['set'])) {
                    $merged[$i]['set'] += $g['set'];
                    $merged[$i]['alt'] ??= $g['alt'];

                    continue 2;
                }
            }
            $merged[] = $g;
        }
        $out = [];
        foreach ($merged as $g) {
            $out[$this->originalOf(array_values($g['set']))['path']] = $g;
        }

        return $out;
    }

    /** The full-size file of a srcset: the widest one, or one without a -300x200 suffix */
    private function originalOf(array $set): array
    {
        usort($set, fn ($a, $b) => $b['width'] <=> $a['width']);
        foreach ($set as $s) {
            if (! preg_match('#-\d+x\d+\.\w+$#', $s['path'])) {
                return $s;
            }
        }

        return $set[0];
    }

    // ---------------------------------------------------------------- blog

    /** @return array<string, Category> */
    private function importCategories(): array
    {
        $out = [];
        foreach ($this->views as $slug => $view) {
            if (! preg_match('#^category-([a-z0-9-]+?)$#', $slug, $m) || str_contains($slug, '-page-')) {
                continue;
            }
            preg_match('#<h1 class="page-title h-archive-title">(.*?)</h1>#s', $view['content'], $h1);
            preg_match('#<section class="h-archive-description">.*?<p>(.*?)</p>#s', $view['content'], $p);
            preg_match('#\bcategory-(\d+)\b#', $view['body_class'], $id);
            $category = Category::create([
                'slug' => $m[1],
                'name' => html_entity_decode(trim(strip_tags($h1[1] ?? $m[1])), ENT_QUOTES | ENT_HTML5),
                'description' => isset($p[1]) ? html_entity_decode($p[1], ENT_QUOTES | ENT_HTML5) : null,
                'body_class' => $view['body_class'],
                'assets' => $view['assets'] + ['paged' => $this->views[$slug.'-page-2']['assets'] ?? $view['assets']],
                'legacy_id' => $id[1] ?? null,
            ]);
            $this->attachSeo($category, $view['head']);
            if (isset($this->views[$slug.'-page-2'])) {
                [, $paged] = $this->parseHead($this->views[$slug.'-page-2']['head']);
                foreach ($paged as $schema) {
                    $category->schemaBlocks()->create($schema + ['variant' => 'paged']);
                }
            }
            $out[$m[1]] = $category;
        }

        return $out;
    }

    /** @param array<string, Category> $categories */
    private function importPosts(array $categories): void
    {
        // Excerpts and thumbnails come from the archive cards
        $cards = [];
        foreach ($this->views as $slug => $view) {
            if (! str_starts_with($slug, 'category-')) {
                continue;
            }
            preg_match_all('#<article\b.*?</article>#s', $view['content'], $m);
            foreach ($m[0] as $card) {
                preg_match('#<h2 class="entry-title[^"]*"><a href="/([^/"]+)/"#', $card, $u);
                preg_match('#<div class="h-excerpt-container[^"]*">\s*<p>(.*?)</p>#s', $card, $e);
                preg_match('#<img\b[^>]*\ssrc="([^"]+)"#', $card, $i);
                $cards[$u[1]] = ['excerpt' => isset($e[1]) ? html_entity_decode($e[1], ENT_QUOTES | ENT_HTML5) : null, 'image' => $i[1] ?? null];
            }
        }

        foreach ($this->views as $slug => $view) {
            if (! str_starts_with($view['body_class'], 'single ')) {
                continue;
            }
            $c = $view['content'];
            preg_match('#<article\s+class="([^"]+)" id="entry-(\d+)">#', $c, $art);
            preg_match('#<h1 class="entry-title">(.*?)</h1>#s', $c, $title);
            preg_match('#<span\s+class="author-name"\s*>\s*(.*?)\s*</span>#s', $c, $author);
            preg_match_all('#\bcategory-([a-z0-9-]+)\b#', $art[1], $cat);
            [$seo] = $this->parseHead($view['head']);

            $image = $cards[$slug]['image'] ?? null;
            $media = $image ? Media::where('path', $image)->first() ?? Media::whereJsonContains('sizes', ['path' => $image])->first() : null;
            if (! $media && $image) {
                foreach (Media::all() as $m) {
                    if (in_array($image, $m->paths(), true)) {
                        $media = $m;
                        break;
                    }
                }
            }

            $post = Post::create([
                'slug' => $slug,
                'title' => html_entity_decode($title[1], ENT_QUOTES | ENT_HTML5),
                'excerpt' => $cards[$slug]['excerpt'] ?? null,
                'content' => $this->postContent($c),
                'featured_image' => str_contains($art[1], 'has-post-thumbnail') ? $media?->path : null,
                'author_name' => html_entity_decode(trim($author[1] ?? 'Abu Bakar'), ENT_QUOTES | ENT_HTML5),
                'status' => 'published',
                'published_at' => Carbon::parse($seo['published_time']),
                'modified_at' => Carbon::parse($seo['modified_time'] ?? $seo['published_time']),
                'body_class' => $view['body_class'],
                'assets' => $view['assets'],
                'legacy_id' => (int) $art[2],
            ]);
            foreach ($cat[1] as $catSlug) {
                if (isset($categories[$catSlug])) {
                    $post->categories()->attach($categories[$catSlug]);
                }
            }
            $this->attachSeo($post, $view['head']);
        }
    }

    /** The inner HTML of the post's entry-content */
    private function postContent(string $html): string
    {
        $at = strpos($html, '<div class="entry-content clear"');
        $element = substr($html, $at, HtmlBlocks::children(substr($html, $at))[0][1]);
        $parts = HtmlBlocks::split($element) ?? throw new RuntimeException('No post content');

        return $parts[1];
    }

    // ---------------------------------------------------------------- pages

    private function importPages(): void
    {
        foreach ($this->views as $slug => $view) {
            if (str_starts_with($view['body_class'], 'single ') || str_starts_with($slug, 'category-')) {
                continue;
            }
            [$open, $close, $chunks] = $this->sections($view['content']);
            preg_match('#\b(?:pg|x-page)-(\d+)\b#', $view['body_class'], $id);
            preg_match('#<link rel="canonical" href="([^"]+)"#', $view['head'], $canonical);

            $page = Page::create([
                'slug' => $slug,
                'path' => $slug === 'home' ? '/' : ($slug === 'get-a-free-audit' ? '/get-a-free-audit/' : ($canonical[1] ?? Page::pathFor($slug))),
                'title' => html_entity_decode(preg_match('#<title>(.*?)</title>#s', $view['head'], $t) ? $t[1] : $slug, ENT_QUOTES | ENT_HTML5),
                'template' => $slug === 'our-blog' ? 'blog_index' : 'sections',
                'status' => in_array($slug, self::hiddenSlugs(), true) ? 'hidden' : 'published',
                'body_class' => $view['body_class'],
                'wrapper_open' => $open,
                'wrapper_close' => $close,
                'assets' => $view['assets'],
                'legacy_id' => $id[1] ?? null,
            ]);
            $keys = [];
            foreach ($chunks as $i => $html) {
                $type = 'html';
                $content = ['html' => $html];
                if (str_contains($html, 'class="x-posts-container')) {
                    $type = 'posts_grid';
                    $at = strpos($html, '<div class="x-posts-container');
                    $grid = substr($html, $at, HtmlBlocks::children(substr($html, $at))[0][1]);
                    [$gridOpen, $cards, $gridClose] = HtmlBlocks::split($grid);
                    $content = ['html' => str_replace($grid, $gridOpen.PageSection::POSTS_MARKER.$gridClose, $html), 'count' => substr_count($cards, '<article ')];
                }
                $key = $this->sectionKey($html, $i);
                $keys[$key] = ($keys[$key] ?? 0) + 1;
                $page->sections()->create([
                    'key' => $keys[$key] > 1 ? $key.'-'.$keys[$key] : $key,
                    'type' => $type,
                    'sort_order' => ($i + 1) * 10,
                    'content' => $content,
                    'is_visible' => true,
                ]);
            }
            $this->attachSeo($page, $view['head']);
        }
    }

    /** The industry pages that were hidden in SiteController before they had a status */
    private static function hiddenSlugs(): array
    {
        return ['beauty-salons', 'aesthetic-clinics', 'hair-salons', 'nail-salons', 'day-spas', 'medical-spas', 'dermatology-clinics',
            'laser-hair-removal-clinics', 'barber-shops', 'lash-studios', 'brow-studios', 'makeup-studios', 'beauty-academies',
            'wellness-centers', 'arabic-beauty-salon', 'cosmetic-makeup-beauty-brand', 'hair-care-beauty-brand', 'hair-extension-studios',
            'hammam-center', 'korean-beauty-salon', 'ladies-salon-marketing', 'massage-centers', 'moroccan-bath-centre',
            'plastic-surgery-clinics', 'russian-beauty-salon', 'skincare-beauty-brand', 'skin-care-clinics'];
    }

    /**
     * Wrapper markup and section chunks: down through single-child wrappers to the element that
     * holds the sections. Chunks run from one section to the next, so wrapper + chunks + wrapper
     * gives back the original bytes.
     *
     * @return array{0:string,1:string,2:list<string>}
     */
    private function sections(string $html): array
    {
        $open = '';
        $close = '';
        while (true) {
            $children = HtmlBlocks::children($html);
            if (count($children) !== 1) {
                break;
            }
            [$a, $b] = $children[0];
            $element = substr($html, $a, $b - $a);
            $parts = HtmlBlocks::split($element);
            if (! $parts || preg_match('#^<section\b|\bc-parent\b#', $parts[0]) || trim(substr($html, 0, $a).substr($html, $b)) !== '') {
                break;
            }
            $open .= substr($html, 0, $a).$parts[0];
            $close = $parts[2].substr($html, $b).$close;
            $html = $parts[1];
        }

        $children = HtmlBlocks::children($html);
        $chunks = [];
        foreach ($children as $i => [$a]) {
            $from = $i === 0 ? 0 : $a;
            $to = $children[$i + 1][0] ?? strlen($html);
            $chunks[] = substr($html, $from, $to - $from);
        }

        return [$open, $close, $chunks ?: [$html]];
    }

    /** A readable name for a section: its id, or what its classes say it is */
    private function sectionKey(string $html, int $i): string
    {
        $open = preg_match('#<[a-z]+\b[^>]*>#i', ltrim($html), $m) ? $m[0] : '';
        if (preg_match('#\sid="([a-z][\w-]*)"#i', $open, $id) && ! preg_match('#^(bg|lp-[a-z-]+-\d+)$#', $id[1])) {
            return strtolower($id[1]);
        }
        foreach (['hero', 'faq', 'cta', 'stats', 'reviews', 'testimonials', 'form', 'services', 'pricing', 'steps', 'process', 'nav', 'areas', 'links', 'features'] as $word) {
            if (preg_match('#class="[^"]*\b(lp-)?'.$word.'\b#', $open)) {
                return $word;
            }
        }
        if (preg_match('#class="(lp-[a-z]+)#', $open, $c)) {
            return substr($c[1], 3);
        }

        return 'section-'.($i + 1);
    }
}
