<?php

use App\Http\Controllers\LeadController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\HandleRedirects;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// The site has no logins: skip sessions and cookies so pages carry no Set-Cookie header and
// can be cached by the browser, the server and any CDN.
Route::withoutMiddleware([
    StartSession::class,
    ShareErrorsFromSession::class,
    ValidateCsrfToken::class,
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
])->group(function () {
    // Website forms and the audit funnel (public/js/site.js, public/js/pages/get-a-free-audit.js)
    Route::post('/api/enquiry', [LeadController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/api/audit', [LeadController::class, 'audit'])->middleware('throttle:10,1');

    Route::get('/robots.txt', [SitemapController::class, 'robots']);
    Route::get('/{name}.xml', [SitemapController::class, 'show'])
        ->where('name', 'sitemap_index|sitemap|post-sitemap|page-sitemap|category-sitemap');

    // Every page, post and category archive, from the database. Old addresses in the
    // redirects table are sent on first (App\Http\Middleware\HandleRedirects).
    Route::get('/{path?}', [SiteController::class, 'show'])->where('path', '.*')->middleware(HandleRedirects::class);
});
