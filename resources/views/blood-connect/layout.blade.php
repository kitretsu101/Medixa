@extends('layouts.public')
@section('title', 'Medixa Blood Connect')
@section('content')
<div class="mx-auto flex max-w-6xl flex-col gap-8 px-4 py-10 sm:px-6 lg:px-8">
    <nav aria-label="Blood Connect" class="flex flex-wrap items-center gap-4 text-sm font-semibold text-red-800">
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
    <p class="rounded-2xl border border-stone-200 bg-white p-4 text-sm leading-relaxed text-stone-600">Matching is a preliminary red-cell discovery aid, not medical clearance. Qualified medical professionals must confirm compatibility and donation eligibility. Contact the treating hospital directly for urgent care; this service does not arrange emergency dispatch.</p>
</div>
@endsection

