<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'Medixa — Smart Hospital OPD System. Book appointments, find doctors, and manage your health records with ease.')">
    <title>@yield('title', 'Medixa') — Smart Hospital OPD</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Outfit', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        medixa: {
                            50:  '#edfaf7',
                            100: '#d2f4ec',
                            200: '#a9e8da',
                            300: '#72d5c2',
                            400: '#3ebba6',
                            500: '#22a08c',
                            600: '#188071',
                            700: '#16675c',
                            800: '#16524a',
                            900: '#16453e',
                            950: '#072824',
                        },
                        surface: {
                            900: '#0d1117',
                            800: '#161b22',
                            700: '#21262d',
                            600: '#30363d',
                            500: '#3d444d',
                            400: '#656d76',
                            300: '#9198a1',
                            200: '#b9bfc8',
                            100: '#d1d7de',
                        },
                        accent: {
                            400: '#f0883e',
                            300: '#ffa657',
                        }
                    }
                }
            }
        }
    </script>
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-[#0d1117] text-[#d1d7de] font-sans antialiased min-h-screen flex flex-col">

    {{-- Navbar --}}
    @include('partials.navbar')

    {{-- Main content --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('partials.footer')

    {{-- Chatbot Widget --}}
    @include('partials.chatbot-widget')

</body>
</html>
