<?php

use App\Http\Controllers\LeadController;
use App\Http\Controllers\SiteController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// The site is static HTML with no logins: skip sessions and cookies so pages carry no
// Set-Cookie header and can be cached by the browser, the server and any CDN.
Route::withoutMiddleware([
    StartSession::class,
    ShareErrorsFromSession::class,
    ValidateCsrfToken::class,
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
])->group(function () {
    // Old URLs. A controller action rather than closures, because `php artisan route:cache`
    // loses the variables a closure captures.
    Route::get('/privacy-policy', [SiteController::class, 'moved'])->defaults('to', 'privacy-policy-2');
    // The audit funnel lives at /get-a-free-audit/ (storage/app/live-html/get-a-free-audit.html)
    Route::get('/free-audit', [SiteController::class, 'moved'])->defaults('to', 'get-a-free-audit');
    Route::get('/our-services', [SiteController::class, 'moved'])->defaults('to', 'services');
    // Stale WordPress author archive (was a copy of the home page)
    Route::get('/author/abu-bakar', [SiteController::class, 'moved'])->defaults('to', 'about-us');

    // Elementor form submissions (the page scripts still use the WordPress endpoint)
    Route::post('/wp-admin/admin-ajax.php', [LeadController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/wp-json/glowth/v1/audit', [LeadController::class, 'audit'])->middleware('throttle:10,1');

    Route::get('/{path?}', [SiteController::class, 'show'])->where('path', '.*');
});
