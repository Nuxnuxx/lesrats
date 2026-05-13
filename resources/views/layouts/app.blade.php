<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="lesrats-api-url" content="{{ rtrim(config('app.url'), '/') }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="/logo.png">
        <link rel="apple-touch-icon" href="/logo-192.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- ApexCharts -->
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Flash Messages -->
            @if (session('success') || session('error') || session('warning') || session('info'))
                <div class="mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                    @if (session('success'))
                        <x-ui.flash-message type="success" class="mb-4">{{ session('success') }}</x-ui.flash-message>
                    @endif
                    @if (session('error'))
                        <x-ui.flash-message type="error" class="mb-4">{{ session('error') }}</x-ui.flash-message>
                    @endif
                    @if (session('warning'))
                        <x-ui.flash-message type="warning" class="mb-4">{{ session('warning') }}</x-ui.flash-message>
                    @endif
                    @if (session('info'))
                        <x-ui.flash-message type="info" class="mb-4">{{ session('info') }}</x-ui.flash-message>
                    @endif
                </div>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        {{-- Extension gate: locks the whole app if the LesRats extension is missing or outdated. --}}
        @auth
            @include('layouts.extension-gate')
        @endauth

        <!-- Page-specific scripts -->
        @stack('scripts')
    </body>
</html>
