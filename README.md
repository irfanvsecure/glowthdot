# glowthdot

The Glowth website (glowthdot.com): a Laravel app whose pages, blog, images, SEO and settings live in the database, with an MCP server so Claude can manage the whole site (claude.ai custom connector).

## Where things are

| What | Where |
| --- | --- |
| Pages and their sections, blog posts, categories, tags, images, SEO tags, JSON-LD, redirects, settings | the database (`database/migrations/2026_10_28_000001_create_content_tables.php`, `app/Models/`) |
| How a page is rendered | `app/Support/SiteRenderer.php`, templates in `resources/views/site/` |
| Shared head, header, menu, footer | `resources/views/layouts/site.blade.php`, `resources/views/partials/` (their text and links come from settings) |
| Routes | `routes/web.php` (forms, sitemaps, robots.txt, then every page from the database), `routes/ai.php` (MCP) |
| MCP server and tools | `app/Mcp/Servers/SiteServer.php`, `app/Mcp/Tools/` |
| CSS, JS, images, fonts | `public/css/`, `public/js/`, `public/images/`, `public/fonts/` |
| Form and audit leads | `app/Http/Controllers/LeadController.php` (`POST /api/enquiry`, `POST /api/audit`) |
| The content as it was imported | `database/import/` (read once by `php artisan site:import`) |

### How content is built

- **Page:** wrapper markup (`wrapper_open` … `wrapper_close`) around its sections, in order. A section's `content.html` is its exact markup; the design is in those classes and in the page's stylesheets (`assets`).
- **posts_grid section:** shows the latest blog posts where its HTML has `<!--posts-->`.
- **Blog post:** lives at `/{slug}/`; its archive at `/category/{slug}/`, 10 posts a page.
- **Header menu, footer columns, contact details, top bar:** settings.
- **Hidden page** (`status` hidden): still served, but `noindex`, and links to it go to the free audit. The 27 industry pages are hidden for now.
- **Caching:** rendered pages, sitemaps and settings are cached per content version. Every write through the MCP tools bumps the version, so changes show on the next request.

## Deployment

The first time, on the server:

```
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan site:import          # once: fills the database from database/import/
```

Set the MCP secret in `.env` (at least 32 characters):

```
php -r "echo bin2hex(random_bytes(32));"     # copy the output
MCP_SECRET=<that value>
```

Then clear and rebuild the caches:

```
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Notes:

- **Later deploys:** `git pull`, `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force` and the cache commands. **Do not run `site:import` again.** It refuses once content exists, and `--fresh` would throw away every change made since.
- **Database:** SQLite is in `database/database.sqlite`. It must be writable by the web server, and the folder too (SQLite writes a journal next to it). To use MySQL instead, set `DB_CONNECTION=mysql` and the `DB_*` values before `migrate`.
- **Image uploads:** these need GD with WebP (`php -r "var_dump(function_exists('imagewebp'));"`) and a writable `public/images/`.
- **Static files:** `public/sitemap*.xml` and `public/robots.txt` no longer exist. They are served from the database, so make sure the server has no old copies.
- **Cloudflare:** if Cloudflare (or another proxy) is put in front, the site must trust it (`trustProxies` in `bootstrap/app.php`). Otherwise `/mcp` sees plain HTTP and answers 403.

## MCP server (claude.ai connector)

The endpoint is `POST /mcp`, over HTTPS only. It needs the secret, either as a header:

```
Authorization: Bearer <MCP_SECRET>
```

or in the address, for clients that cannot send headers:

```
https://glowthdot.com/mcp/<MCP_SECRET>
```

Calls without the right secret get 401. The endpoint has no session, cookies or CSRF. It allows 120 calls a minute per IP. Every call is logged in `storage/logs/mcp-YYYY-MM-DD.log`, and every change goes to the `activity_log` table (the `activity_log` tool) with before and after values.

**Adding it in claude.ai:**

1. Open **Settings → Connectors → Add custom connector**.
2. Name: `Glowthdot.com`.
3. Remote MCP server URL: `https://glowthdot.com/mcp/<MCP_SECRET>`. Leave the OAuth fields empty.
4. Click **Add**, then enable the connector in a chat (the tools menu). Claude sees the 58 tools below.

To change the secret, change `MCP_SECRET`, run `php artisan config:cache`, and update the connector URL.

Safety:

- There are no tools for SQL, shell commands, files or `.env`.
- HTML sent to the tools is cleaned (`app/Services/HtmlSanitizer.php`): scripts other than JSON-LD, `<style>`, event handlers, `javascript:` links and iframes other than YouTube, Vimeo and Google Maps are removed. Every other byte stays as sent.
- **Slugs:** a slug must be valid and free across pages and posts. A new slug on a published page, post or category adds a 301 from the old address.
- **Deletes:** everything goes to the trash first (`list_trash`, `restore_item`). `purge_item` needs `confirm: true`, and `delete_image` refuses an image that is in use unless `force: true`.
- **Revisions:** every section change keeps one (`list_section_revisions`, `restore_section_revision`).

## Tools

**Pages & sections**

| Tool | What it does |
|---|---|
| `list_pages` | List the site's pages: id, slug, address, title, status, template and number of sections. *(read only)* |
| `get_page` | One page with its settings, SEO tags and a list of its sections (key, type, visible, a text preview). *(read only)* |
| `create_page` | Create a page at /{slug}/. It takes the look (body classes, stylesheets, wrapper markup) of an existing page given in "like" (default about-us) and starts with the sections you give, or none. |
| `update_page` | Change a page's title, slug, status (published, draft, hidden) or template. |
| `delete_page` | Move a page to the trash (restore_item brings it back). |
| `get_page_sections` | The sections of a page in order, with their full HTML. *(read only)* |
| `add_page_section` | Add a section to a page: after the section given in "after" (a key), or at the end. |
| `update_page_section` | Change a section: give find + replace for a small text or link change (the text must occur exactly once unless replace_all), or html for the whole section. |
| `delete_page_section` | Move a section to the trash (restore_item with type "section" brings it back). |
| `reorder_page_sections` | Put a page's sections in a new order: the list of all their keys, in the order you want. |
| `toggle_section_visibility` | Show or hide a section without deleting it. |
| `list_section_revisions` | Earlier versions of a section (one is kept on every change), newest first. *(read only)* |
| `restore_section_revision` | Put a section back to an earlier version (list_section_revisions). |
| `search_content` | Find text across all page sections, blog posts and SEO titles/descriptions. *(read only)* |
| `replace_text` | Find and replace text everywhere (page sections, blog post content, excerpts and SEO texts). |

**Blog**

| Tool | What it does |
|---|---|
| `list_blogs` | List blog posts, newest first: id, slug, title, status (draft, published, scheduled), dates, categories. *(read only)* |
| `get_blog` | One blog post with its full content (HTML), excerpt, featured image, categories, tags and SEO tags. *(read only)* |
| `create_blog` | Write a new blog post at /{slug}/. Content is HTML (headings h2/h3, paragraphs, lists, figures, tables). |
| `update_blog` | Change a blog post: title, slug, content (full HTML, or find + replace), excerpt, featured image, author, categories, tags. |
| `delete_blog` | Move a blog post to the trash (restore_item with type "post" brings it back). |
| `publish_blog` | Publish a blog post now (or keep its date if it already has one in the past). |
| `unpublish_blog` | Take a blog post off the site: it becomes a draft. |
| `schedule_blog` | Publish a blog post at a later date and time (ISO 8601, e.g. |
| `list_categories` | Blog categories with their number of published posts and archive address. *(read only)* |
| `create_category` | Add a blog category, with an archive at /category/{slug}/. |
| `update_category` | Rename a category, change its slug (adds a 301 from the old archive) or its description. |
| `delete_category` | Move a category to the trash. Its posts stay; they just leave this category. |
| `list_tags` | Blog tags with their number of posts. *(read only)* |
| `create_tag` | Add a blog tag. |
| `set_blog_taxonomies` | Set a post's categories and/or tags (slugs). The lists replace what the post had. |
| `list_stale_posts` | Published posts whose content has not changed for a number of days (default 180), oldest first: candidates for a refresh. *(read only)* |

**Images**

| Tool | What it does |
|---|---|
| `list_images` | The image library: path, size, alt text, resized copies and where each image is used (pages, posts, SEO, settings, stylesheets). *(read only)* |
| `upload_image` | Add an image from a public URL or base64 data. The file is kept as sent, and a WebP copy (at most 2560 px wide) plus 300/768/1024/1536 px copies for srcset are made. |
| `update_image` | Change an image's alt text or title, or rename its files (every page, post and SEO tag that uses it is updated). |
| `replace_image` | Swap an image for another everywhere it is used (page sections, posts, featured images, SEO images, settings). |
| `delete_image` | Move an image to the trash. Refused while the image is used somewhere, unless force: true. |

**SEO**

| Tool | What it does |
|---|---|
| `get_seo` | The SEO tags of a page, blog post or category: title, meta description, canonical, robots, Open Graph and Twitter tags. *(read only)* |
| `update_seo` | Change SEO tags of a page, blog post or category. Send only the fields to change; an empty string clears one. |
| `audit_seo` | Check the live site, or one address, for SEO problems: missing or duplicate titles and descriptions, lengths, no H1 or several, images without alt text, thin content (under 300 words), missing canonical or og:image, noindex pages in the sitemap. *(read only)* |
| `get_schema` | The JSON-LD blocks of a page, blog post or category (id, type, JSON). *(read only)* |
| `update_schema` | Add, replace or remove JSON-LD on a page, blog post or category: give json (an object with @context and @type) to add a block, block_id + json to replace one, or block_id + remove: true. |
| `generate_schema` | Build valid JSON-LD from the site's own data: Organization or LocalBusiness (settings), Service (a page), FAQPage (the page's question/answer blocks, or the faq list you give), BreadcrumbList (the address), Article (a blog post). |
| `get_internal_links` | Links between the site's own pages: for one address, the pages it links to (outbound) and the pages that link to it (inbound); for the whole site, each page's counts and the pages nothing links to. *(read only)* |
| `check_broken_links` | Find links that lead nowhere: every internal link on the live site is checked, and with include_external also links to other sites (HTTP status, up to 150 of them). *(read only)* |
| `list_redirects` | Old addresses that send visitors on: from, to, 301 or 302, and how often each was used. *(read only)* |
| `save_redirect` | Send an old address to a new one (301 permanent by default, or 302 temporary). |
| `delete_redirect` | Stop redirecting an old address (by id or from address). |
| `validate_sitemap_robots` | Check the sitemaps and robots.txt: every listed address loads and is indexable, published pages and posts are all listed, hidden and draft ones are not, and robots.txt points at the sitemap. *(read only)* |
| `update_robots` | Replace the contents of /robots.txt. Keep a "Sitemap: https://glowthdot.com/sitemap_index.xml" line. |

**Site settings**

| Tool | What it does |
|---|---|
| `get_settings` | The site-wide settings: contact details (phone, WhatsApp, email, address), social links, header top bar and button, header menu, footer tagline, link columns and copyright, robots defaults. *(read only)* |
| `update_settings` | Change one or more site settings: {"settings": {"phone": "+971...", "header_cta": {"label": "...", "url": "/..."}}}. |

**Safety & ops**

| Tool | What it does |
|---|---|
| `list_trash` | Everything deleted and not yet purged: pages, sections, posts, categories, tags, images, redirects and schema blocks, newest first. *(read only)* |
| `restore_item` | Bring something back from the trash (list_trash gives type and id). |
| `purge_item` | Delete something in the trash for good (cannot be undone; an image's files are removed too). |
| `activity_log` | What was changed, by whom and when, with the values before and after. *(read only)* |
| `clear_cache` | Clear the site's caches: rendered pages and sitemaps, compiled views, the combined stylesheets, config and routes (rebuilt straight away), and Cloudflare when it is set up. |
| `audit_performance` | Page weight of one address or the whole site: HTML, stylesheet, script and image bytes, the heaviest images, images without loading="lazy" below the first screen, and images with no width/height. *(read only)* |
| `get_site_info` | An overview: counts of pages, sections, posts, categories, tags, images, redirects; the latest changes; versions of PHP, Laravel and this MCP server. *(read only)* |

## Checks

```
php artisan test
```

The tests cover:

- the pages and their content
- forms and leads
- page speed
- redirects
- every MCP tool: auth, validation, create/update/delete/restore
- the HTML sanitizer

`docs/phase1-diff-report.md` compares every page before and after the move to the database. `scripts/visual/compare-all.ps1` takes full-page screenshots of two running copies of the site and reports the pixels that changed.
