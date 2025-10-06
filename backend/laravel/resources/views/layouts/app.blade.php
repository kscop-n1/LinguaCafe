<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="/manifest.json"> 
    <link rel="icon" type="image/png" href="/icon512rounded.png">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>LinguaCafe</title>

    @if (env('FRONTEND_BUILD') === 'vue3')
        @vite(['src/app.ts'])
    @else
        <link href="{{ mix('css/app.css') }}" rel="stylesheet">
        <script src="{{ mix('js/app.js') }}" defer></script>
    @endif
    <link rel="dns-prefetch" href="//fonts.gstatic.com">

    <!-- These are dynamically set with javascript -->
    <style id="dynamic-default-font"></style>
    <style id="dynamic-selected-font"></style>
    
    @yield('header')
</head>
<body>
<div id="app">
    @yield('content')
</div>
</body></html>
