<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', 'Blood Connect Donors') · Medixa</title>
    @if(file_exists(public_path('build/manifest.json'))) @vite(['resources/css/app.css']) @endif
</head>
<body class="min-h-screen bg-stone-50 text-stone-800">
<main class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-10 sm:px-6">
    <nav aria-label="Donor portal" class="flex flex-wrap items-center gap-4 text-sm font-semibold text-red-800">
        <a href="{{ route('blood-connect.index') }}">Blood Connect</a>
        <a href="{{ route('blood-connect.donors.index') }}">Find Donors</a>
        @auth('donor')
            <a href="{{ route('blood-connect.donor.dashboard') }}">Donor Dashboard</a>
            <a href="{{ route('blood-connect.donor.requests') }}">My Requests</a>
            <form method="POST" action="{{ route('blood-connect.donor.logout') }}">@csrf<button>Donor Logout</button></form>
        @else
            <a href="{{ route('blood-connect.donor.register') }}">Become a Donor</a>
            <a href="{{ route('blood-connect.donor.login') }}">Donor Login</a>
        @endauth
    </nav>
    @include('blood-connect.partials.messages')
    @yield('donor-content')
    <p class="rounded-xl border border-stone-200 bg-white p-4 text-sm">This service connects people only. Medical staff must confirm donor eligibility and compatibility. It does not arrange emergency dispatch.</p>
</main>
</body>
</html>
