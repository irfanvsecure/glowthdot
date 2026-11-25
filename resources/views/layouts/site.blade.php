<!DOCTYPE html>
<html lang="en-US">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preload" href="/fonts/inter-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/fonts/space-grotesk-latin.woff2" as="font" type="font/woff2" crossorigin>
@if (setting('google_site_verification'))
<meta name="google-site-verification" content="{{ setting('google_site_verification') }}">
@endif
<meta name="robots" content="{{ $robots }}">
@include('partials.seo-head')
<meta property="og:locale" content="{{ setting('og_locale', 'en_US') }}">
<meta property="og:site_name" content="{{ setting('site_name', 'Glowth') }}">
<meta name="twitter:card" content="{{ $seo->twitter_card ?? setting('twitter_card', 'summary_large_image') }}">
<link rel="icon" href="/images/2026/07/cropped-cropped-glowth-logo-transparent-32x32.png" sizes="32x32">
<link rel="icon" href="/images/2026/07/cropped-cropped-glowth-logo-transparent-192x192.png" sizes="192x192">
<link rel="apple-touch-icon" href="/images/2026/07/cropped-cropped-glowth-logo-transparent-180x180.png">
{{-- Stylesheets in cascade order; App\Support\PageSpeed serves them as one file --}}
<link rel="stylesheet" href="{{ versioned('/css/fonts.css') }}">
@foreach ($assets['styles'] ?? [] as $file)
<link rel="stylesheet" href="{{ versioned($file) }}">
@endforeach
<link rel="stylesheet" href="{{ versioned('/css/site.css') }}">
<link rel="stylesheet" href="{{ versioned('/css/site-align.css') }}">
@foreach ($assets['theme_styles'] ?? [] as $file)
<link rel="stylesheet" href="{{ versioned($file) }}">
@endforeach
<link rel="stylesheet" href="{{ versioned('/css/v2.css') }}">
@foreach ($assets['late_styles'] ?? [] as $file)
<link rel="stylesheet" href="{{ versioned($file) }}">
@endforeach
<link rel="stylesheet" href="{{ versioned('/css/fixes.css') }}">
</head>
<body class="{{ $bodyClass }}">
{{-- The menu and scroll-to-top arrow, drawn once and reused (<use href="#gl-arrow">) --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><symbol id="gl-arrow" viewBox="57 35.171 26 16.043"><path d="M57.5 38.193l12.5 12.5 12.5-12.5-2.5-2.5-10 10-10-10z"/></symbol></svg>
{{-- Sets the desktop / mobile header classes before the header is drawn --}}
<script src="{{ versioned('/js/header-breakpoint.js') }}"></script>
<a class="skip-link screen-reader-text" href="#content">Skip to content</a>
<div class="hf site" id="page">
@include('partials.header')
<div id="content" class="site-content">
@yield('content')
</div>
@include('partials.footer')
</div>
@include('partials.floating-buttons')
@foreach ($assets['scripts'] ?? [] as $file)
<script src="{{ versioned($file) }}" defer></script>
@endforeach
<script src="{{ versioned('/js/site.js') }}" defer></script>
@foreach ($assets['theme_scripts'] ?? [] as $file)
<script src="{{ versioned($file) }}" defer></script>
@endforeach
<script src="{{ versioned('/js/v2.js') }}" defer></script>
</body>
</html>
