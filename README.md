# glowthdot

The Glowth website (glowthdot.com), a Laravel app with plain HTML pages.

## Where things are

| What | Where |
| --- | --- |
| Pages (plain HTML) | `resources/views/pages/{page}.blade.php` |
| Shared head, header, footer | `resources/views/layouts/site.blade.php`, `resources/views/partials/` |
| One route per page | `routes/web.php` |
| Site-wide CSS and JS | `public/css/`, `public/js/` |
| A page's own CSS and JS | `public/css/pages/{page}.css` (and `{page}-widgets.css`), `public/js/pages/{page}.js` |
| Images, fonts | `public/images/`, `public/fonts/` |
| Form and audit leads | `app/Http/Controllers/LeadController.php` (`POST /api/enquiry`, `POST /api/audit`) |

## Adding a page

1. Copy a page in `resources/views/pages/` (for example `about-us.blade.php`) to `new-page.blade.php`
   and change its `head` section (title, description, canonical, social tags) and its `content`.
2. Add its route to `routes/web.php`:
   `Route::get('/new-page', [SiteController::class, 'page'])->defaults('page', 'new-page');`
3. Add it to `public/page-sitemap.xml`.

Write links site-relative (`/about-us/`); canonical, social and schema URLs become full URLs when the page is served.

## How a page is served

`SiteController::page` renders the view, then:

- links to the hidden industry pages (`SiteController::HIDDEN_PAGES`) go to the free audit and those pages are `noindex`;
- emoji are wrapped in `<span class="emoji">` so they do not change line widths (`app/Support/Emoji.php`);
- the stylesheets in `<head>` are served as one cached file from `public/css/bundle/` (`app/Support/PageSpeed.php`).

Use `versioned('/js/file.js')` for links to files in `public/`, so browsers fetch the new copy after an edit.

## Checks

    php artisan test

`scripts/visual/compare-all.ps1` takes full-page screenshots of two running copies of the site and reports the pixels that changed.
