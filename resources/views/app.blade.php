@php
    $site = $page['props']['site'] ?? [];
    $seo = $page['props']['seo'] ?? null;
    $siteName = $site['name'] ?? config('app.name');
    $documentTitle = ($seo['title'] ?? null) ? $seo['title'].' | '.$siteName : $siteName;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
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
                background-color: oklch(0.985 0.003 85);
            }

            html.dark {
                background-color: oklch(0.155 0.004 70);
            }
        </style>

        <meta name="theme-color" content="#f8f7f4" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#161412" media="(prefers-color-scheme: dark)">

        @if (! empty($site['faviconUrl']))
            <link rel="icon" href="{{ $site['faviconUrl'] }}">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @endif
        <link rel="apple-touch-icon" href="{{ $site['faviconUrl'] ?? '/apple-touch-icon.png' }}">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ $documentTitle }}</title>
            @if ($seo)
                <meta data-inertia="description" name="description" content="{{ $seo['description'] }}">
                <meta data-inertia="robots" name="robots" content="{{ $seo['robots'] }}">
                <link data-inertia="canonical" rel="canonical" href="{{ $seo['url'] }}">
                <meta data-inertia="og:site_name" property="og:site_name" content="{{ $siteName }}">
                <meta data-inertia="og:type" property="og:type" content="{{ $seo['type'] }}">
                <meta data-inertia="og:title" property="og:title" content="{{ $documentTitle }}">
                <meta data-inertia="og:description" property="og:description" content="{{ $seo['description'] }}">
                <meta data-inertia="og:url" property="og:url" content="{{ $seo['url'] }}">
                <meta data-inertia="twitter:card" name="twitter:card" content="{{ $seo['image'] ? 'summary_large_image' : 'summary' }}">
                <meta data-inertia="twitter:title" name="twitter:title" content="{{ $documentTitle }}">
                <meta data-inertia="twitter:description" name="twitter:description" content="{{ $seo['description'] }}">
                @if ($seo['image'])
                    <meta data-inertia="og:image" property="og:image" content="{{ $seo['image'] }}">
                    <meta data-inertia="twitter:image" name="twitter:image" content="{{ $seo['image'] }}">
                @endif
                @foreach ($seo['jsonLd'] as $index => $schema)
                    <script data-inertia="jsonld-{{ $index }}" type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
                @endforeach
            @else
                <meta name="robots" content="noindex, nofollow">
            @endif
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
