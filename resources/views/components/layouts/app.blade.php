<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Brand Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('brand/apple-touch-icon.png') }}">

    {{-- Web App Manifest --}}
    <link rel="manifest" href="{{ route('manifest') }}">

    {{-- Browser Theme Color --}}
    <meta name="theme-color" content="{{ config('branding.colors.theme', '#e83535') }}">
    <meta name="msapplication-TileColor" content="{{ config('branding.colors.ink', '#1A1A1A') }}">

    <title>{{ $title ?? config('app.name', 'Okina Business System') }}</title>

    {{-- Critical CSS to eliminate FOUC, theme flash, and layout jump during navigation --}}
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background-color: #fafaf9; /* ink-50 page surface */
            color: #1a1816;           /* ink-900 primary text */
        }
        .layout-sidebar {
            background-color: #1a1816 !important; /* ink-900 sidebar surface */
            color: #ffffff !important;
        }
        .layout-main {
            background-color: #fafaf9; /* Opaque background prevents ghost outlines in view transitions */
        }
        [x-cloak] {
            display: none !important;
        }
        /* Suppress unwanted transitions during initial page mount */
        .no-transitions,
        .no-transitions * {
            transition: none !important;
        }
        /* Pre-render collapsed sidebar width on desktop before Alpine boots */
        html.sidebar-collapsed aside.layout-sidebar {
            width: 5rem !important; /* 80px */
        }
        html.sidebar-collapsed aside.layout-sidebar .sidebar-label,
        html.sidebar-collapsed aside.layout-sidebar button[aria-controls] {
            display: none !important;
        }
    </style>

    {{-- Synchronous pre-paint setup to prevent layout and theme jumps --}}
    <script>
        (function () {
            try {
                document.documentElement.classList.add('no-transitions');
                if (localStorage.getItem('sidebarCollapsed') === 'true') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[color:var(--color-surface-page,#fafaf9)] text-[color:var(--color-text-body,#2a2724)] font-sans">
    {{ $slot }}
    <x-toast />

    {{-- Restore transitions smoothly once initial render is established --}}
    <script>
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                document.documentElement.classList.remove('no-transitions');
            });
        });
    </script>
</body>
</html>
