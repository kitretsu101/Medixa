@extends('blood-connect.layout')
@section('blood-content')
<h1 class="font-display text-3xl font-semibold text-stone-900">Find blood requests</h1>
<form method="GET" action="{{ route('blood-connect.requests.index') }}" class="grid gap-4 rounded-2xl border border-rose-200 bg-white p-5 shadow-sm sm:p-7 sm:grid-cols-2 lg:grid-cols-3">
    <x-blood-connect.field name="q" label="Search requests" :value="request('q')" maxlength="100" />
    <x-blood-connect.select name="blood_group" label="Blood group" :options="$groups" :value="request('blood_group')" placeholder="All groups" />
    <x-blood-connect.field name="district" label="District" :value="request('district')" maxlength="100" />
    <x-blood-connect.field name="area" label="Location" :value="request('area')" maxlength="255" />
    <x-blood-connect.field name="hospital" label="Hospital" :value="request('hospital')" maxlength="255" />
    <x-blood-connect.select name="emergency_level" label="Emergency level" :options="['normal','urgent','critical']" :value="request('emergency_level')" placeholder="All levels" />
    <x-blood-connect.field name="required_date" label="Required date" type="date" :value="request('required_date')" />
    <x-blood-connect.select name="sort" label="Sort by" :options="['newest','needed_soon','priority']" :value="request('sort', 'newest')" />
    <div class="flex items-end gap-4"><button class="rounded-xl bg-[#DC2626] px-5 py-3 font-semibold text-white">Filter requests</button><a class="text-sm underline" href="{{ route('blood-connect.requests.index') }}">Reset</a></div>
</form>
<div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">@forelse($requests as $bloodRequest)@include('blood-connect.partials.card')@empty<p>No active requests match your search.</p>@endforelse</div>
{{ $requests->links() }}
@endsection

