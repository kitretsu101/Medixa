@extends('blood-connect.independent.layout')
@section('title', 'Find blood donors')
@section('donor-content')
<div class="rounded-3xl border border-rose-200 bg-linear-to-br from-red-700 to-rose-600 p-6 text-white shadow-sm sm:p-9"><p class="mb-2 text-xs font-bold uppercase tracking-widest text-rose-100">Medixa Blood Connect</p><h1 class="text-3xl font-bold sm:text-4xl">Find blood donors</h1></div>
<p>Available donors who consent to public profiles appear here. Phone verification is optional. Send a request; the donor can call you directly.</p>
<form method="GET" action="{{ route('blood-connect.donors.index') }}" class="grid gap-4 rounded-2xl border border-rose-200 bg-white p-5 shadow-sm sm:p-6 sm:grid-cols-2 lg:grid-cols-4">
    <x-blood-connect.select name="blood_group" label="Blood group" :options="$groups" :value="request('blood_group')" placeholder="All groups" />
    <x-blood-connect.select name="district" label="District" :options="$districts" :value="request('district')" placeholder="All districts" />
    <x-blood-connect.field name="area" label="Area / location" :value="request('area')" maxlength="255" />
    <x-blood-connect.select name="availability_status" label="Availability" :options="['available','temporarily_unavailable','unavailable']" :value="request('availability_status')" placeholder="All eligible donors" />
    <button class="justify-self-start rounded-xl bg-[#DC2626] px-5 py-2 font-semibold text-white shadow-sm transition hover:bg-[#B91C1C] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Filter donors</button>
</form>
<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
@forelse($donors as $donor)
    <article class="flex flex-col gap-3 rounded-2xl border border-rose-200 bg-white p-5 shadow-sm transition-shadow sm:p-7 hover:shadow-md">
        <h2 class="break-words text-xl font-bold text-stone-900">{{ $donor->full_name }}</h2>
        <p class="text-2xl font-bold text-red-800">{{ $donor->blood_group }}</p>
        <p>{{ $donor->district }} · {{ $donor->area }}</p>
        <span class="self-start rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800 ring-1 ring-emerald-200">Available</span>
        <a class="mt-auto self-start rounded-xl bg-[#DC2626] px-4 py-2 font-semibold text-white shadow-sm transition hover:bg-[#B91C1C] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600" href="{{ route('blood-connect.donors.request', $donor) }}">Request this donor</a>
    </article>
@empty
    <p>No available verified donors match these filters.</p>
@endforelse
</div>
{{ $donors->links() }}
@endsection
