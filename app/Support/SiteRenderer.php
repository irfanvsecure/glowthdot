<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\SeoMeta;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Turns a URL path into the finished HTML of a page, post or category archive. The result is
 * cached per content version, so any content write shows on the next request.
 */
class SiteRenderer
{
    public function __construct(private PageSpeed $speed, private Emoji $emoji) {}

    /**
     * @return array{html: string, noindex: bool}|null null when nothing lives at $path
     */
    public function render(string $path, bool $shot = false): ?array
    {
        $path = self::normalize($path);
        $key = 'page:'.ContentVersion::get().':'.md5($path).($shot ? ':shot' : '');

        if (! $shot && ($cached = Cache::get($key))) {
            return $cached;
        }

        $result = $this->build($path);
        if ($result === null) {
            return null;
        }
        $html = $this->finish($result['html'], $shot);
        $out = ['html' => $html, 'noindex' => $result['noindex']];
        if (! $shot) {
            Cache::put($key, $out, now()->addDay());
        }

        return $out;
    }

    /** "/about-us/" for "about-us", "/about-us" and "/about-us/" */
    public static function normalize(string $path): string
    {
        $path = '/'.trim(parse_url($path, PHP_URL_PATH) ?: '', '/');

        return $path === '/' ? '/' : $path.'/';
    }

    /** What lives at a path: ['page', Page] | ['post', Post] | ['category', Category, n] | null */
    public static function resolve(string $path, bool $drafts = false): ?array
    {
        $path = self::normalize($path);

        $page = Page::where('path', $path)->when(! $drafts, fn ($q) => $q->whereIn('status', ['published', 'hidden']))->first();
        if ($page) {
            return ['page', $page];
        }
        if (preg_match('#^/category/([a-z0-9-]+)/(?:page/(\d+)/)?$#', $path, $m)) {
            $category = Category::where('slug', $m[1])->first();
            $n = (int) ($m[2] ?? 1);

            // page/1/ is not an address of its own: page 1 is /category/{slug}/
            return $category && $n >= 1 && ($m[2] ?? null) !== '1' ? ['category', $category, $n] : null;
        }
        if (preg_match('#^/([a-z0-9-]+)/$#', $path, $m)) {
            $post = Post::where('slug', $m[1])->when(! $drafts, fn ($q) => $q->live())->first();
            if ($post) {
                return ['post', $post];
            }
        }

        return null;
    }

    /** Paths of the pages that are hidden: links to them go to the free audit */
    public static function hiddenPaths(): array
    {
        return Cache::rememberForever('hidden-paths:'.ContentVersion::get(),
            fn () => Page::where('status', 'hidden')->pluck('path')->all());
    }

    private function build(string $path): ?array
    {
        $found = self::resolve($path);
        if (! $found) {
            return null;
        }

        return match ($found[0]) {
            'page' => $this->page($found[1]),
            'post' => $this->post($found[1]),
            'category' => $this->category($found[1], $found[2]),
        };
    }

    private function page(Page $page): array
    {
        $noindex = $page->status === 'hidden';
        $html = view('site.page', $this->common($page, $page->seo, $noindex) + [
            'page' => $page,
            'sections' => $page->sections()->where('is_visible', true)->get(),
            'latestPosts' => fn (int $count) => Post::live()->with('categories')->orderByDesc('published_at')->limit($count)->get(),
        ])->render();

        return ['html' => $html, 'noindex' => $noindex];
    }

    private function post(Post $post): array
    {
        $order = Post::live()->orderBy('published_at')->orderBy('id')->get(['id', 'slug', 'title', 'published_at']);
        $at = $order->search(fn ($p) => $p->id === $post->id);
        $html = view('site.post', $this->common($post, $post->seo, false) + [
            'post' => $post->load('categories'),
            'previous' => $at > 0 ? $order[$at - 1] : null,
            'next' => $order[$at + 1] ?? null,
        ])->render();

        return ['html' => $html, 'noindex' => false];
    }

    private function category(Category $category, int $n): ?array
    {
        $posts = $category->posts()->live()->with('categories')->orderByDesc('published_at')->orderByDesc('id')->get();
        $pages = max(1, (int) ceil($posts->count() / Category::PER_PAGE));
        if ($n > $pages) {
            return null;
        }
        $paginator = new LengthAwarePaginator($posts->forPage($n, Category::PER_PAGE)->values(), $posts->count(), Category::PER_PAGE, $n);

        $seo = $category->seo?->replicate() ?? new SeoMeta;
        $schemas = $category->schemaBlocks;
        if ($n > 1) {
            // og:image: the first post on this page that has one
            $first = collect($paginator->items())->first(fn ($p) => $p->featured_image);
            [$seo, $schemas] = $this->pagedSeo($category, $seo, $schemas, $n, $pages, $first);
        } else {
            $seo->prev = null;
            $seo->next = $pages > 1 ? $category->url(2) : null;
        }

        $common = $this->common($category, $seo, false, $schemas);
        $assets = $category->assets ?? [];
        $common['assets'] = $n > 1 ? ($assets['paged'] ?? $assets) : array_diff_key($assets, ['paged' => 1]);
        $html = view('site.category', $common + [
            'category' => $category,
            'posts' => $paginator,
        ])->render();

        return ['html' => $html, 'noindex' => false];
    }

    /** Page n of an archive: its own title, address, prev/next and image, from page 1's tags */
    private function pagedSeo(Category $category, SeoMeta $seo, Collection $schemas, int $n, int $pages, ?Post $first): array
    {
        $suffix = ' - '.setting('site_name', 'Glowth');
        $title = fn (?string $t) => $t && str_ends_with($t, $suffix)
            ? substr($t, 0, -strlen($suffix))." - Page $n of $pages".$suffix
            : ($t ? "$t - Page $n of $pages" : null);
        $seo->title = $title($seo->title);
        $seo->og_title = $title($seo->og_title);
        // og:url stays the archive's first page, as before
        $seo->canonical = $category->url($n);
        $seo->prev = $category->url($n - 1);
        $seo->next = $n < $pages ? $category->url($n + 1) : null;

        if ($image = $first?->image()) {
            $sized = $image->sized(1024);
            $seo->og_image = $sized['path'];
            $copy = $sized['path'] !== $image->path;
            $seo->og_image_width = $copy ? $sized['width'] : null;
            $seo->og_image_height = $copy ? $sized['height'] : null;
            $seo->og_image_type = $copy ? $image->mime : null;
        }

        // JSON-LD of page 2 (stored as the "paged" variant), renumbered for page n
        $paged = $category->pagedSchemaBlocks;
        if ($paged->isNotEmpty()) {
            $schemas = $paged->map(function ($block) use ($n, $pages) {
                $block = $block->replicate();
                $block->json = str_replace(['page\/2\/', 'Page 2 of 2'], ['page\/'.$n.'\/', "Page $n of $pages"], $block->json);

                return $block;
            });
        }

        return [$seo, $schemas];
    }

    private function common($model, ?SeoMeta $seo, bool $noindex, ?Collection $schemas = null): array
    {
        return [
            'seo' => $seo,
            'schemas' => $schemas ?? $model->schemaBlocks,
            'assets' => $model->assets ?? [],
            'bodyClass' => $model->body_class ?? '',
            'robots' => $noindex ? 'noindex, nofollow' : ($seo?->robots ?: setting('robots_default', 'index, follow')),
        ];
    }

    /** What every page gets on its way out */
    private function finish(string $html, bool $shot): string
    {
        $html = $this->hideLinksToHiddenPages($html);
        $html = $this->absoluteSeoUrls($html);
        $html = $this->emoji->wrap($html);

        // Visual-regression screenshots (?__shot=1): no motion, so before/after compare pixel for pixel
        if ($shot) {
            $html = preg_replace(
                '/<\/head>/i',
                '<style>*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important}'
                .'.gp-hero-step,.v2-r,.x-invisible{opacity:1!important;visibility:visible!important;transform:none!important}</style></head>',
                $html,
                1
            ) ?? $html;
        }

        return $this->speed->optimize($html);
    }

    /** Links (not canonical tags or schema) to a hidden page point at the free audit instead */
    private function hideLinksToHiddenPages(string $html): string
    {
        $paths = self::hiddenPaths();
        if ($paths === []) {
            return $html;
        }
        $alternation = implode('|', array_map(fn ($p) => preg_quote(trim($p, '/'), '#'), $paths));

        return preg_replace(
            '#(<a\b[^>]*?\bhref=)(["\'])(?:https?://(?:www\.)?glowthdot\.com)?/(?:'.$alternation.')/?(?:[?\#][^"\']*)?\2#i',
            '$1$2/get-a-free-audit/$2',
            $html
        ) ?? $html;
    }

    /** Search engines and social previews need full URLs; the content writes them site-relative */
    private function absoluteSeoUrls(string $html): string
    {
        $site = config('app.canonical_url');

        $html = preg_replace(
            '#(<link\s+rel=["\'](?:canonical|prev|next)["\']\s+href=["\']|<meta\s+(?:property|name)=["\'](?:og:url|og:image|twitter:image)["\']\s+content=["\'])/(?!/)#i',
            '$1'.$site.'/',
            $html
        ) ?? $html;

        // schema.org JSON-LD stores URLs with escaped slashes: "url":"\/about-us\/"
        $jsonSite = str_replace('/', '\/', $site);

        return preg_replace_callback(
            '#(<script type="application/ld\+json"[^>]*>)(.*?)(</script>)#s',
            fn ($m) => $m[1].preg_replace('#"(\\\\/)(?!\\\\/)#', '"'.$jsonSite.'$1', $m[2]).$m[3],
            $html
        ) ?? $html;
    }
}
