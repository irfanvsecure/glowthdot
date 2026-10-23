<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Glowth')</title>
    @hasSection('description')
        <meta name="description" content="@yield('description')">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @yield('styles')
    <link rel="icon" href="/favicon.ico">
</head>
<body {!! trim($__env->yieldContent('body_attrs')) !!}>
@yield('content')
@yield('scripts')
</body>
</html>