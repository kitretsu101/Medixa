@extends('blood-connect.layout')
@section('blood-content')
<section class="rounded-3xl border border-red-100 bg-white p-6 sm:p-10">
    <p class="text-sm font-semibold uppercase tracking-widest text-red-700">Medixa Blood Connect</p>
    <h1 class="mt-4 max-w-3xl font-display text-4xl font-semibold text-stone-900 sm:text-5xl">Donate Blood. Save Lives. Be Someone's Hope.</h1>
    <p class="mt-5 max-w-2xl leading-relaxed text-stone-600">Connect with your community. Post a blood request for administrator review, find verified requests, or make yourself available as a donor.</p>
    <div class="mt-6 flex flex-wrap gap-3">
        <a class="rounded-full bg-red-700 px-6 py-3 font-semibold text-white" href="{{ route('blood-connect.requests.create') }}">Request Blood</a>
        <a class="rounded-full border border-stone-300 px-6 py-3 font-semibold" href="{{ route('blood-connect.requests.index') }}">Find Blood</a>
        <a class="rounded-full border border-red-200 px-6 py-3 font-semibold text-red-800" href="{{ route('blood-connect.donor.register') }}">Become a Donor</a>
    </div>
</section>
<section aria-label="Emergency blood requests" class="grid gap-4 sm:grid-cols-2">
    <div class="rounded-2xl bg-red-700 p-6 text-white"><h2 class="text-lg font-bold">Emergency blood requests</h2><p class="mt-2">{{ $criticalCount }} active critical request(s). Coordinate with the treating hospital directly.</p><a class="mt-3 inline-block underline" href="{{ route('blood-connect.requests.index', ['emergency_level' => 'critical']) }}">See critical requests</a></div>
    <div class="rounded-2xl border border-stone-200 bg-white p-6"><p class="text-4xl font-bold text-red-800">{{ $activeCount }}</p><h2 class="mt-2 font-semibold">Approved, active blood requests</h2><p class="mt-2 text-sm text-stone-600">New requests stay private until an administrator approves them.</p></div>
</section>
<section><h2 class="mb-4 text-2xl font-semibold text-stone-900">Recently posted requests</h2><div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">@forelse($requests as $bloodRequest)@include('blood-connect.partials.card')@empty<p>No active requests right now.</p>@endforelse</div></section>
<section class="rounded-2xl border border-stone-200 bg-white p-6"><h2 class="text-xl font-semibold">Giving blood, with care</h2><p class="mt-3 leading-relaxed text-stone-600">Keep your availability and last donation date up to date. Expressing interest is an offer to help; it does not confirm a donation. Discuss eligibility, screening, and safe donation with qualified staff at the hospital.</p><a class="mt-3 inline-block text-sm text-red-800 underline" href="https://www.redcrossblood.org/donate-blood/blood-types.html" rel="noopener noreferrer">Learn about blood types from the American Red Cross</a></section>
@endsection

