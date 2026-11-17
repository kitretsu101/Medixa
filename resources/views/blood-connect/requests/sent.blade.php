@extends('blood-connect.layout')
@section('blood-content')
<section class="rounded-2xl border border-stone-200 bg-white p-8">
    <h1 class="text-3xl font-semibold">Your blood request has been submitted</h1>
    <p class="mt-4">It remains private while an administrator reviews it.</p>
    @if(session('request_code'))<p class="mt-4 break-all font-semibold">Request code: {{ session('request_code') }}</p>@endif
    <a class="mt-6 inline-block text-red-800 underline" href="{{ route('blood-connect.requests.index') }}">View active blood requests</a>
</section>
@endsection

