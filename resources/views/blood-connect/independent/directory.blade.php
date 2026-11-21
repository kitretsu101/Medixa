@extends('blood-connect.independent.layout')
@section('title', 'Find blood donors')
@section('donor-content')
<h1 class="text-3xl font-bold">Find blood donors</h1>
<p>Only phone-verified, available donors who consent to public profiles appear here. Send a request; the donor can call you directly.</p>
<form method="GET" action="{{ route('blood-connect.donors.index') }}" class="grid gap-4 rounded-2xl border border-stone-200 bg-white p-5 sm:grid-cols-2 lg:grid-cols-4">
    <x-blood-connect.select name="blood_group" label="Blood group" :options="$groups" :value="request('blood_group')" placeholder="All groups" />
    <x-blood-connect.select name="district" label="District" :options="$districts" :value="request('district')" placeholder="All districts" />
    <x-blood-connect.field name="area" label="Area / location" :value="request('area')" maxlength="255" />
    <x-blood-connect.select name="availability_status" label="Availability" :options="['available','temporarily_unavailable','unavailable']" :value="request('availability_status')" placeholder="All eligible donors" />
    <button class="justify-self-start rounded-xl bg-red-700 px-5 py-2 text-white">Filter donors</button>
</form>
<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
@forelse($donors as $donor)
    <article class="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-white p-6">
        <h2 class="text-xl font-bold">{{ $donor->full_name }}</h2>
        <p class="text-2xl font-bold text-red-800">{{ $donor->blood_group }}</p>
        <p>{{ $donor->district }} · {{ $donor->area }}</p>
        <p>Available</p>
        <a class="self-start rounded-xl bg-red-700 px-4 py-2 text-white" href="{{ route('blood-connect.donors.request', $donor) }}">Request this donor</a>
    </article>
@empty
    <p>No available verified donors match these filters.</p>
@endforelse
</div>
{{ $donors->links() }}
@endsection
