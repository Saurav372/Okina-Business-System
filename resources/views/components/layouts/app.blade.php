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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-50 text-surface-900 font-sans">
    {{ $slot }}
    <x-toast />
</body>
</html>
