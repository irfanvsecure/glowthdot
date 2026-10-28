# Phase 1: old vs new rendered pages

Every public address was rendered before the content moved to the database (commit `1a38994`, the pages as Blade files) and again after (pages, sections, posts, categories, SEO and settings from the database), and the two were compared.

## How

For each of the 83 addresses, both versions were compared on:

- the visible text of the page (header, body, footer; tags removed, whitespace collapsed)
- every image `src`, in order
- every link `href`, in order
- every script file, in order
- the stylesheets inside the combined CSS bundle, in order (the cascade)
- every head tag: title, meta description, robots, canonical, prev/next, Open Graph, Twitter, article dates, author
- every JSON-LD block (parsed)
- the `<body>` classes

Full-page screenshots of 9 representative pages at 1440 px and 500 px (home, about, a location page, a service page, two blog posts, both category archive pages and the blog page) were also compared pixel by pixel (`scripts/visual/compare-all.ps1`).

## Result

**79 of 83 pages are the same on every point above.** The other 4 differ by one invisible line break, explained below. No content, image, link, stylesheet, script or head tag changed.

Screenshots: 16 of 18 are pixel-identical. The other two (`/category/salon-marketing/` and `/our-blog/` at 1440 px) differ only where lazy-loaded images had not finished loading in one of the shots; the cards, text and layout are the same.

### The 4 posts with a whitespace difference

`/salon-marketing-calendar-dubai/`, `/salon-not-showing-google-maps/`, `/salon-website-conversion-fixes/`, `/salon-whatsapp-booking-leak/`

In these four posts the old markup had a line break between the post title (`<h1>`) and the "By Abu Bakar / date" line; the other 26 posts did not. All posts now come from one template without it. Both are block elements, so nothing moves on screen (the screenshot of `/salon-marketing-calendar-dubai/` is pixel-identical); only a text comparison that collapses whitespace sees "Summer By" become "SummerBy".

### JSON-LD URLs (a fix, compared after correction)

Since commit `7d4d9f8` the live site has served JSON-LD with site-relative URLs (`"@id":"\/about-us\/"`) instead of full ones, because a regular expression in `SiteController` lost two backslashes in that rewrite. Before it (commit `eee4473`) they were full URLs. The new renderer makes them full again (`"@id":"https:\/\/glowthdot.com\/about-us\/"`). The comparison above makes the old JSON-LD URLs full before comparing, so it checks that nothing else in the schema changed.

## Per page

| Page | Result |
|---|---|
| /about-us/ | same |
| /abu-dhabi/ | same |
| /aesthetic-clinics/ | same |
| /ai-appointment-booking-bot/ | same |
| /arabic-beauty-salon/ | same |
| /barber-shops/ | same |
| /beauty-academies/ | same |
| /beauty-marketing-cost-dubai/ | same |
| /beauty-salons/ | same |
| /booking/ | same |
| /brow-studios/ | same |
| /category/clinic-marketing/ | same |
| /category/clinic-marketing/page/2/ | same |
| /category/salon-marketing/ | same |
| /category/salon-marketing/page/2/ | same |
| /choose-marketing-agency-dubai/ | same |
| /clinic-bad-review-response-dha/ | same |
| /clinic-consultation-conversion-dha/ | same |
| /clinic-google-ads-keywords/ | same |
| /clinic-google-maps-ranking/ | same |
| /clinic-google-reviews-dubai/ | same |
| /clinic-instagram-ads-cost-dubai/ | same |
| /clinic-marketing-uae-guide/ | same |
| /clinic-no-show-consultations-dha/ | same |
| /clinic-packages-membership-pricing-dha/ | same |
| /clinic-patient-retention-dha/ | same |
| /clinic-retail-skincare-products-dha/ | same |
| /clinic-social-media-content-ideas-dha/ | same |
| /clinic-website-conversion-dha/ | same |
| /clinic-whatsapp-booking-bot-dha/ | same |
| /contact-us/ | same |
| /cosmetic-makeup-beauty-brand/ | same |
| /day-spas/ | same |
| /dermatology-clinics/ | same |
| /dubai/ | same |
| /ecommerce-for-beauty/ | same |
| /faq/ | same |
| /get-a-free-audit/ | same |
| /google-ads-for-beauty/ | same |
| /hair-care-beauty-brand/ | same |
| /hair-extension-studios/ | same |
| /hair-salons/ | same |
| /hammam-center/ | same |
| / | same |
| /industries/ | same |
| /instagram-facebook-ads-for-beauty/ | same |
| /korean-beauty-salon/ | same |
| /ladies-salon-marketing/ | same |
| /laser-hair-removal-clinics/ | same |
| /lash-studios/ | same |
| /makeup-studios/ | same |
| /massage-centers/ | same |
| /medical-spas/ | same |
| /moroccan-bath-centre/ | same |
| /nail-salons/ | same |
| /our-blog/ | same |
| /plastic-surgery-clinics/ | same |
| /privacy-policy-2/ | same |
| /reviews-reputation/ | same |
| /russian-beauty-salon/ | same |
| /salon-bad-review-response-templates/ | same |
| /salon-client-retention-loyalty-dubai/ | same |
| /salon-content-ideas-30-days/ | same |
| /salon-ecommerce-beauty-products/ | same |
| /salon-google-ads-keywords/ | same |
| /salon-google-reviews-dubai/ | same |
| /salon-instagram-ads-cost-dubai/ | same |
| /salon-marketing-calendar-dubai/ | **differs**: text @726: OLD «ubai: Plan the Year Around Ramadan, Eid and Summer By Abu Bakar / October 12, 2026 Most Dubai salons market in bursts: a» NEW «ubai: Plan the Year Around Ramadan, Eid and SummerBy Abu Bakar / October 12, 2026 Most Dubai salons market in bursts: a » |
| /salon-marketing-dubai-guide/ | same |
| /salon-membership-subscription-pricing-dubai/ | same |
| /salon-no-shows-cancellations-dubai/ | same |
| /salon-not-showing-google-maps/ | **differs**: text @724: OLD «w Up in “Near Me” Searches (And How to Fix It) By Abu Bakar / August 5, 2026 Try this experiment. Stand inside your » NEW «w Up in “Near Me” Searches (And How to Fix It)By Abu Bakar / August 5, 2026 Try this experiment. Stand inside your o» |
| /salon-website-conversion-fixes/ | **differs**: text @719: OLD « Brochure. Here’s How to Make It Book. (7 Fixes) By Abu Bakar / August 25, 2026 Open your salon’s website on your ph» NEW « Brochure. Here’s How to Make It Book. (7 Fixes)By Abu Bakar / August 25, 2026 Open your salon’s website on your pho» |
| /salon-whatsapp-booking-leak/ | **differs**: text @718: OLD « Salons Lose Bookings After 7pm (And Never Notice) By Abu Bakar / August 6, 2026 Every salon owner tracks certain number» NEW « Salons Lose Bookings After 7pm (And Never Notice)By Abu Bakar / August 6, 2026 Every salon owner tracks certain numbers» |
| /seo-for-beauty-salons-clinics/ | same |
| /services/ | same |
| /sharjah/ | same |
| /skin-care-clinics/ | same |
| /skincare-beauty-brand/ | same |
| /social-media-management-for-beauty/ | same |
| /terms-of-service/ | same |
| /websites-landing-pages-for-beauty/ | same |
| /wellness-centers/ | same |
