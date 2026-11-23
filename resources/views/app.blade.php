@php
    $appearance = in_array(request()->cookie('appearance'), ['light', 'dark', 'system'], true) ? request()->cookie('appearance') : 'dark';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => $appearance === 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="dark light">
        <meta name="robots" content="noindex, nofollow">
        <meta name="csp-nonce" content="{{ Vite::cspNonce() }}">

        @if ($appearance === 'system')
            <script nonce="{{ Vite::cspNonce() }}">
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) document.documentElement.classList.add('dark');
            </script>
        @endif

        <title inertia>{{ config('app.name') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @routes(nonce: Vite::cspNonce())
        @viteReactRefresh
        @vite(['resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
