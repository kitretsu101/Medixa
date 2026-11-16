@extends('layouts.public')

@section('title', 'Request Sent — Medixa')

@section('content')
<section class="mx-auto max-w-xl px-4 py-16">
    <div class="rounded-2xl border border-stone-200 bg-white p-8">
        <h1 class="font-display text-3xl font-semibold text-stone-800">Your request has been sent</h1>
        <p class="mt-4 text-sm text-stone-600">The reception desk will review your request. This confirmation does not mean an ambulance has been dispatched or a bed has been reserved.</p>
        <a href="{{ route('home') }}" class="mt-6 inline-flex rounded-full bg-[#5eaea0] px-6 py-3 text-sm font-semibold text-white hover:bg-[#4c9d90]">Return home</a>
    </div>
</section>
@endsection
