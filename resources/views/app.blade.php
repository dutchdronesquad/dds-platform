<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @php
            $seo = data_get($page, 'props.seo');
            $siteName = data_get($seo, 'openGraph.siteName', 'Dutch Drone Squad');
            $pageTitle = data_get($seo, 'title');
            $documentTitle = $pageTitle && $pageTitle !== $siteName ? "{$pageTitle} - {$siteName}" : $siteName;
        @endphp
        <x-inertia::head>
            <title>{{ $documentTitle }}</title>

            @if (is_array($seo))
                <meta data-inertia="description" name="description" content="{{ data_get($seo, 'description') }}">
                <meta data-inertia="robots" name="robots" content="{{ data_get($seo, 'robots') }}">
                <link data-inertia="canonical" rel="canonical" href="{{ data_get($seo, 'canonicalUrl') }}">
                <meta data-inertia="og:title" property="og:title" content="{{ data_get($seo, 'openGraph.title') }}">
                <meta data-inertia="og:description" property="og:description" content="{{ data_get($seo, 'openGraph.description') }}">
                <meta data-inertia="og:type" property="og:type" content="{{ data_get($seo, 'openGraph.type') }}">
                <meta data-inertia="og:url" property="og:url" content="{{ data_get($seo, 'openGraph.url') }}">
                <meta data-inertia="og:image" property="og:image" content="{{ data_get($seo, 'openGraph.image') }}">
                <meta data-inertia="og:image:alt" property="og:image:alt" content="{{ data_get($seo, 'openGraph.imageAlt') }}">
                <meta data-inertia="og:site_name" property="og:site_name" content="{{ data_get($seo, 'openGraph.siteName') }}">
            @endif
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
