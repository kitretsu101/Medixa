@extends('blood-connect.independent.layout')
@section('title', 'Request sent')
@section('donor-content')
<section class="rounded-2xl border border-stone-200 bg-white p-6">
    <h1 class="text-2xl font-bold">Your request has been sent</h1>
    <p class="mt-3">The chosen donor can see your request and call you directly. This does not confirm availability or a donation.</p>
    <a href="{{ route('blood-connect.donors.index') }}" class="mt-4 inline-block text-red-800 underline">Return to donor directory</a>
</section>
@endsection
