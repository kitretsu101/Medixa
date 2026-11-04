<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Medixa') }} Medicine Tracker</title>

    <!-- PWA Web App Manifest -->
    <link rel="manifest" href="/manifest-tracker.json">
    <meta name="theme-color" content="#168071">
    <meta name="mobile-web-app-capable" content="yes">

    <!-- iOS Safari PWA Meta -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Medixa Tracker">
    <link rel="apple-touch-icon" href="/tracker-assets/icons/icon-192.svg">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|outfit:600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts & Styles via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Smooth PWA mobile animations and touch highlights */
        * {
            -webkit-tap-highlight-color: transparent;
        }
        [x-cloak] { display: none !important; }
        .safe-bottom {
            padding-bottom: env(safe-area-inset-bottom, 1rem);
        }
        .safe-top {
            padding-top: env(safe-area-inset-top, 1rem);
        }
        @keyframes pulse-gentle {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.04); opacity: 0.95; }
        }
        .animate-pulse-gentle {
            animation: pulse-gentle 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-slate-50 selection:bg-teal-500 selection:text-white flex flex-col justify-between">

    {{ $slot }}

    <!-- PWA Service Worker Registration & Install Prompt Handler -->
    <script>
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw-tracker.js', { scope: '/tracker' })
                    .then(reg => {
                        console.log('Medixa Tracker ServiceWorker active with scope:', reg.scope);
                    })
                    .catch(err => {
                        console.warn('Medixa Tracker ServiceWorker registration failed:', err);
                    });
            });
        }

        // Global deferred prompt holder for PWA installation
        window.deferredPwaPrompt = null;
        window.isAppInstalled = false;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            window.deferredPwaPrompt = e;
            window.dispatchEvent(new CustomEvent('pwa-installable'));
        });

        window.addEventListener('appinstalled', () => {
            window.deferredPwaPrompt = null;
            window.isAppInstalled = true;
            window.dispatchEvent(new CustomEvent('pwa-installed'));
        });
    </script>
</body>
</html>

