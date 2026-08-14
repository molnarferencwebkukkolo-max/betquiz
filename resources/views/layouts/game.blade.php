<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif

    <!-- Tailwind CDN + Alpine.js -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Vite alapértelmezett assetek -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- 🎯 ITT A CUSTOM CSS-ED A PUBLIC MAPPÁBÓL: -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="font-sans antialiased @yield('body_class', 'bg-gray-100 text-gray-900')">
<div class="min-h-screen @yield('page_wrapper_class', 'bg-gray-100')">
    @if(view()->exists('layouts.navigation'))
        @include('layouts.navigation')
    @endif

    <main>
        @yield('content')
    </main>
    <x-site-footer />
</div>
</body>
</html>
