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
    Route::get('/free-audit', [SiteController::class, 'moved'])->defaults('to', 'get-a-free-audit');
    Route::get('/our-services', [SiteController::class, 'moved'])->defaults('to', 'services');
    // Old author archive (was a copy of the home page)
    Route::get('/author/abu-bakar', [SiteController::class, 'moved'])->defaults('to', 'about-us');

    // Website forms and the audit funnel (public/js/site.js, public/js/pages/get-a-free-audit.js)
    Route::post('/api/enquiry', [LeadController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/api/audit', [LeadController::class, 'audit'])->middleware('throttle:10,1');

    // Pages: resources/views/pages/{page}.blade.php
    // Main pages
    Route::get('/', [SiteController::class, 'page'])->defaults('page', 'home');
    Route::get('/services', [SiteController::class, 'page'])->defaults('page', 'services');
    Route::get('/industries', [SiteController::class, 'page'])->defaults('page', 'industries');
    Route::get('/about-us', [SiteController::class, 'page'])->defaults('page', 'about-us');
    Route::get('/contact-us', [SiteController::class, 'page'])->defaults('page', 'contact-us');
    Route::get('/faq', [SiteController::class, 'page'])->defaults('page', 'faq');
    Route::get('/booking', [SiteController::class, 'page'])->defaults('page', 'booking');
    Route::get('/get-a-free-audit', [SiteController::class, 'page'])->defaults('page', 'get-a-free-audit');

    // Services
    Route::get('/seo-for-beauty-salons-clinics', [SiteController::class, 'page'])->defaults('page', 'seo-for-beauty-salons-clinics');
    Route::get('/websites-landing-pages-for-beauty', [SiteController::class, 'page'])->defaults('page', 'websites-landing-pages-for-beauty');
    Route::get('/social-media-management-for-beauty', [SiteController::class, 'page'])->defaults('page', 'social-media-management-for-beauty');
    Route::get('/google-ads-for-beauty', [SiteController::class, 'page'])->defaults('page', 'google-ads-for-beauty');
    Route::get('/instagram-facebook-ads-for-beauty', [SiteController::class, 'page'])->defaults('page', 'instagram-facebook-ads-for-beauty');
    Route::get('/ai-appointment-booking-bot', [SiteController::class, 'page'])->defaults('page', 'ai-appointment-booking-bot');
    Route::get('/ecommerce-for-beauty', [SiteController::class, 'page'])->defaults('page', 'ecommerce-for-beauty');
    Route::get('/reviews-reputation', [SiteController::class, 'page'])->defaults('page', 'reviews-reputation');

    // Locations
    Route::get('/dubai', [SiteController::class, 'page'])->defaults('page', 'dubai');
    Route::get('/abu-dhabi', [SiteController::class, 'page'])->defaults('page', 'abu-dhabi');
    Route::get('/sharjah', [SiteController::class, 'page'])->defaults('page', 'sharjah');

    // Industries (hidden for now, see SiteController::HIDDEN_PAGES)
    Route::get('/beauty-salons', [SiteController::class, 'page'])->defaults('page', 'beauty-salons');
    Route::get('/aesthetic-clinics', [SiteController::class, 'page'])->defaults('page', 'aesthetic-clinics');
    Route::get('/hair-salons', [SiteController::class, 'page'])->defaults('page', 'hair-salons');
    Route::get('/nail-salons', [SiteController::class, 'page'])->defaults('page', 'nail-salons');
    Route::get('/day-spas', [SiteController::class, 'page'])->defaults('page', 'day-spas');
    Route::get('/medical-spas', [SiteController::class, 'page'])->defaults('page', 'medical-spas');
    Route::get('/dermatology-clinics', [SiteController::class, 'page'])->defaults('page', 'dermatology-clinics');
    Route::get('/laser-hair-removal-clinics', [SiteController::class, 'page'])->defaults('page', 'laser-hair-removal-clinics');
    Route::get('/barber-shops', [SiteController::class, 'page'])->defaults('page', 'barber-shops');
    Route::get('/lash-studios', [SiteController::class, 'page'])->defaults('page', 'lash-studios');
    Route::get('/brow-studios', [SiteController::class, 'page'])->defaults('page', 'brow-studios');
    Route::get('/makeup-studios', [SiteController::class, 'page'])->defaults('page', 'makeup-studios');
    Route::get('/beauty-academies', [SiteController::class, 'page'])->defaults('page', 'beauty-academies');
    Route::get('/wellness-centers', [SiteController::class, 'page'])->defaults('page', 'wellness-centers');
    Route::get('/arabic-beauty-salon', [SiteController::class, 'page'])->defaults('page', 'arabic-beauty-salon');
    Route::get('/cosmetic-makeup-beauty-brand', [SiteController::class, 'page'])->defaults('page', 'cosmetic-makeup-beauty-brand');
    Route::get('/hair-care-beauty-brand', [SiteController::class, 'page'])->defaults('page', 'hair-care-beauty-brand');
    Route::get('/hair-extension-studios', [SiteController::class, 'page'])->defaults('page', 'hair-extension-studios');
    Route::get('/hammam-center', [SiteController::class, 'page'])->defaults('page', 'hammam-center');
    Route::get('/korean-beauty-salon', [SiteController::class, 'page'])->defaults('page', 'korean-beauty-salon');
    Route::get('/ladies-salon-marketing', [SiteController::class, 'page'])->defaults('page', 'ladies-salon-marketing');
    Route::get('/massage-centers', [SiteController::class, 'page'])->defaults('page', 'massage-centers');
    Route::get('/moroccan-bath-centre', [SiteController::class, 'page'])->defaults('page', 'moroccan-bath-centre');
    Route::get('/plastic-surgery-clinics', [SiteController::class, 'page'])->defaults('page', 'plastic-surgery-clinics');
    Route::get('/russian-beauty-salon', [SiteController::class, 'page'])->defaults('page', 'russian-beauty-salon');
    Route::get('/skincare-beauty-brand', [SiteController::class, 'page'])->defaults('page', 'skincare-beauty-brand');
    Route::get('/skin-care-clinics', [SiteController::class, 'page'])->defaults('page', 'skin-care-clinics');

    // Blog
    Route::get('/our-blog', [SiteController::class, 'page'])->defaults('page', 'our-blog');
    Route::get('/category/salon-marketing', [SiteController::class, 'page'])->defaults('page', 'category-salon-marketing');
    Route::get('/category/salon-marketing/page/2', [SiteController::class, 'page'])->defaults('page', 'category-salon-marketing-page-2');
    Route::get('/category/clinic-marketing', [SiteController::class, 'page'])->defaults('page', 'category-clinic-marketing');
    Route::get('/category/clinic-marketing/page/2', [SiteController::class, 'page'])->defaults('page', 'category-clinic-marketing-page-2');

    // Blog posts
    Route::get('/beauty-marketing-cost-dubai', [SiteController::class, 'page'])->defaults('page', 'beauty-marketing-cost-dubai');
    Route::get('/choose-marketing-agency-dubai', [SiteController::class, 'page'])->defaults('page', 'choose-marketing-agency-dubai');
    Route::get('/clinic-bad-review-response-dha', [SiteController::class, 'page'])->defaults('page', 'clinic-bad-review-response-dha');
    Route::get('/clinic-consultation-conversion-dha', [SiteController::class, 'page'])->defaults('page', 'clinic-consultation-conversion-dha');
    Route::get('/clinic-google-ads-keywords', [SiteController::class, 'page'])->defaults('page', 'clinic-google-ads-keywords');
    Route::get('/clinic-google-maps-ranking', [SiteController::class, 'page'])->defaults('page', 'clinic-google-maps-ranking');
    Route::get('/clinic-google-reviews-dubai', [SiteController::class, 'page'])->defaults('page', 'clinic-google-reviews-dubai');
    Route::get('/clinic-instagram-ads-cost-dubai', [SiteController::class, 'page'])->defaults('page', 'clinic-instagram-ads-cost-dubai');
    Route::get('/clinic-marketing-uae-guide', [SiteController::class, 'page'])->defaults('page', 'clinic-marketing-uae-guide');
    Route::get('/clinic-no-show-consultations-dha', [SiteController::class, 'page'])->defaults('page', 'clinic-no-show-consultations-dha');
    Route::get('/clinic-packages-membership-pricing-dha', [SiteController::class, 'page'])->defaults('page', 'clinic-packages-membership-pricing-dha');
    Route::get('/clinic-patient-retention-dha', [SiteController::class, 'page'])->defaults('page', 'clinic-patient-retention-dha');
    Route::get('/clinic-retail-skincare-products-dha', [SiteController::class, 'page'])->defaults('page', 'clinic-retail-skincare-products-dha');
    Route::get('/clinic-social-media-content-ideas-dha', [SiteController::class, 'page'])->defaults('page', 'clinic-social-media-content-ideas-dha');
    Route::get('/clinic-website-conversion-dha', [SiteController::class, 'page'])->defaults('page', 'clinic-website-conversion-dha');
    Route::get('/clinic-whatsapp-booking-bot-dha', [SiteController::class, 'page'])->defaults('page', 'clinic-whatsapp-booking-bot-dha');
    Route::get('/salon-bad-review-response-templates', [SiteController::class, 'page'])->defaults('page', 'salon-bad-review-response-templates');
    Route::get('/salon-client-retention-loyalty-dubai', [SiteController::class, 'page'])->defaults('page', 'salon-client-retention-loyalty-dubai');
    Route::get('/salon-content-ideas-30-days', [SiteController::class, 'page'])->defaults('page', 'salon-content-ideas-30-days');
    Route::get('/salon-ecommerce-beauty-products', [SiteController::class, 'page'])->defaults('page', 'salon-ecommerce-beauty-products');
    Route::get('/salon-google-ads-keywords', [SiteController::class, 'page'])->defaults('page', 'salon-google-ads-keywords');
    Route::get('/salon-google-reviews-dubai', [SiteController::class, 'page'])->defaults('page', 'salon-google-reviews-dubai');
    Route::get('/salon-instagram-ads-cost-dubai', [SiteController::class, 'page'])->defaults('page', 'salon-instagram-ads-cost-dubai');
    Route::get('/salon-marketing-calendar-dubai', [SiteController::class, 'page'])->defaults('page', 'salon-marketing-calendar-dubai');
    Route::get('/salon-marketing-dubai-guide', [SiteController::class, 'page'])->defaults('page', 'salon-marketing-dubai-guide');
    Route::get('/salon-membership-subscription-pricing-dubai', [SiteController::class, 'page'])->defaults('page', 'salon-membership-subscription-pricing-dubai');
    Route::get('/salon-no-shows-cancellations-dubai', [SiteController::class, 'page'])->defaults('page', 'salon-no-shows-cancellations-dubai');
    Route::get('/salon-not-showing-google-maps', [SiteController::class, 'page'])->defaults('page', 'salon-not-showing-google-maps');
    Route::get('/salon-website-conversion-fixes', [SiteController::class, 'page'])->defaults('page', 'salon-website-conversion-fixes');
    Route::get('/salon-whatsapp-booking-leak', [SiteController::class, 'page'])->defaults('page', 'salon-whatsapp-booking-leak');

    // Legal
    Route::get('/privacy-policy-2', [SiteController::class, 'page'])->defaults('page', 'privacy-policy-2');
    Route::get('/terms-of-service', [SiteController::class, 'page'])->defaults('page', 'terms-of-service');

    // Anything else: 404
    Route::fallback([SiteController::class, 'notFound']);
});
