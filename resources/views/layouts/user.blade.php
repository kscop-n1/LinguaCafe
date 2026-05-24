<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="/manifest.json"> 
    <link rel="icon" type="image/png" href="/icon512rounded.png">
    @if ($theme === 'dark')
        <meta name="theme-color" content="#28272C" />
    @elseif ($theme === 'eink')
        <meta name="theme-color" content="#FFFFFF" />
    @else
        <meta name="theme-color" content="#F2F3F5" />
    @endif

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>LinguaCafe</title>
    <script>window.__LINGUACAFE_WEBSOCKET_APP_KEY = @json(config('broadcasting.connections.pusher.key'));</script>

    @vite(['resources/js/app.js'])
    <script src="/js/dmak/raphael.js"></script>
    <script src="/js/dmak/dmak.js"></script>
    <script src="/js/dmak/dmakLoader.js"></script>
    <link rel="dns-prefetch" href="//fonts.gstatic.com">

    <!-- These are dynamically set with javascript -->
    <style id="dynamic-default-font"></style>
    <style id="dynamic-selected-font"></style>

    @yield('header')
</head>
<body><!--
--><div id="app"><!--
    -->@yield('content')<!--
--></div><!--
--></body></html>
