@extends('layouts.public')
@section('title', 'Medixa Blood Connect')
@section('content')
<div class="bg-linear-to-br from-rose-50/80 via-white to-red-50/60"><div class="mx-auto flex max-w-6xl flex-col gap-8 px-4 py-10 sm:px-6 lg:px-8">
    <nav aria-label="Blood Connect" class="flex flex-wrap items-center gap-2 rounded-2xl border border-rose-200 bg-white p-3 text-sm font-semibold text-red-800 shadow-sm [&_a]:rounded-lg [&_a]:px-3 [&_a]:py-2 [&_a]:transition [&_a:hover]:bg-rose-100 [&_a:focus-visible]:outline-2 [&_a:focus-visible]:outline-red-600">
        <a href="{{ route('blood-connect.index') }}">Blood Connect</a>
        <a href="{{ route('blood-connect.requests.create') }}">Request Blood</a>
        <a href="{{ route('blood-connect.requests.index') }}">Find Blood Requests</a>
        <a href="{{ route('blood-connect.donor.register') }}">Become a Donor</a>
        <a href="{{ route('blood-connect.donors.index') }}">Find Donors</a>
        <a href="{{ route('blood-connect.donor.login') }}">Donor Login</a>
        @auth<a href="{{ route('blood-connect.donor.profile') }}">My donor profile</a>@endauth
    </nav>
    @include('blood-connect.partials.messages')
    @yield('blood-content')
    <p class="rounded-2xl border border-rose-200 bg-rose-50/70 p-4 text-sm leading-relaxed text-stone-600">Matching is a preliminary red-cell discovery aid, not medical clearance. Qualified medical professionals must confirm compatibility and donation eligibility. Contact the treating hospital directly for urgent care; this service does not arrange emergency dispatch.</p>
</div></div>
@endsection

