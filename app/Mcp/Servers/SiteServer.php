<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\BlogTools;
use App\Mcp\Tools\MediaTools;
use App\Mcp\Tools\OpsTools;
use App\Mcp\Tools\PageTools;
use App\Mcp\Tools\SeoTools;
use App\Mcp\Tools\SettingsTools;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Glowthdot.com')]
#[Version('1.0.0')]
#[Instructions(<<<'TXT'
Manage glowthdot.com, a marketing agency site for beauty salons and clinics in the UAE: pages and their sections, blog posts, categories and tags, images, SEO tags, JSON-LD schema, redirects, sitemap, robots.txt and site settings.

How the content is built:
- A page is a list of sections. A section's content is its exact HTML; the site's design lives in that markup and its CSS classes, so change text and links inside it and keep the structure and classes as they are. update_page_section takes a find/replace pair for small edits, or the full HTML.
- A posts_grid section shows the latest blog posts where its HTML has <!--posts-->.
- Blog posts are at /{slug}/, category archives at /category/{slug}/. Header menu, footer links, contact details and the top bar are settings (get_settings).
- Hidden pages (status "hidden") are still served, but noindex, and every link to them goes to the free audit.

Rules:
- Read before you write: get_page / get_page_sections / get_blog / get_seo show what is there now.
- Every section update keeps a revision (list_section_revisions, restore_section_revision).
- Deletes go to the trash (list_trash, restore_item); purge_item removes for good and needs confirm: true. delete_image refuses an image that is in use unless force: true.
- Changing a published slug adds a 301 redirect from the old address.
- Caches and the sitemap refresh by themselves after every change.
- HTML you send is cleaned: scripts (other than JSON-LD), event handlers and javascript: links are removed.
TXT)]
class SiteServer extends Server
{
    // All tools in one tools/list answer
    public int $defaultPaginationLength = 100;

    public int $maxPaginationLength = 100;

    protected function boot(): void
    {
        $this->tools = array_merge(
            PageTools::tools(),
            BlogTools::tools(),
            MediaTools::tools(),
            SeoTools::tools(),
            SettingsTools::tools(),
            OpsTools::tools(),
        );
    }
}
