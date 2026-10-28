<!DOCTYPE html>
<html lang="en-US">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preload" href="/fonts/inter-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/fonts/space-grotesk-latin.woff2" as="font" type="font/woff2" crossorigin>
<meta name="google-site-verification" content="AcTE97RGUOU-zMACvCPa7-TqBj1qv9CysqIL-DOp4rw">
@if ($noindex ?? false)
<meta name="robots" content="noindex, nofollow">
@else
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
@endif
@yield('head')
<meta property="og:locale" content="en_US">
<meta property="og:site_name" content="Glowth">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="/images/2026/07/cropped-cropped-glowth-logo-transparent-32x32.png" sizes="32x32">
<link rel="icon" href="/images/2026/07/cropped-cropped-glowth-logo-transparent-192x192.png" sizes="192x192">
<link rel="apple-touch-icon" href="/images/2026/07/cropped-cropped-glowth-logo-transparent-180x180.png">
{{-- Stylesheets in cascade order; App\Support\PageSpeed serves them as one file --}}
<link rel="stylesheet" href="{{ versioned('/css/fonts.css') }}">
@yield('styles')
<link rel="stylesheet" href="{{ versioned('/css/site.css') }}">
<link rel="stylesheet" href="{{ versioned('/css/site-align.css') }}">
@yield('theme_styles')
<link rel="stylesheet" href="{{ versioned('/css/v2.css') }}">
@yield('late_styles')
</head>
<body class="@yield('body_class')">
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
@yield('scripts')
<script src="{{ versioned('/js/site.js') }}" defer></script>
@yield('theme_scripts')
<script src="{{ versioned('/js/v2.js') }}" defer></script>
</body>
</html>
