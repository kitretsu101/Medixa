@extends('blood-connect.independent.layout')
@section('title', 'Request this donor')
@section('donor-content')
<section class="rounded-2xl border border-stone-200 bg-white p-6">
    <h1 class="text-2xl font-bold">Request {{ $donorCard['full_name'] }}</h1>
    <p class="mt-2">{{ $donorCard['blood_group'] }} · {{ $donorCard['district'] }} · {{ $donorCard['area'] }}</p>
    <p class="mt-2 text-sm">Your details will be visible only to this donor, who can contact you directly. Sending a request does not confirm a donation.</p>
    <form method="POST" action="{{ route('blood-connect.donors.request.store', $donorCard['id']) }}" class="mt-6 grid gap-5 sm:grid-cols-2">
        @csrf
        <x-blood-connect.field name="requester_name" label="Your name (optional)" maxlength="255" />
        <x-blood-connect.field name="requester_phone" label="Your Bangladeshi mobile number" type="tel" required maxlength="20" />
        <x-blood-connect.select name="blood_group_needed" label="Blood group needed" :options="$groups" :value="$donorCard['blood_group']" required />
        <x-blood-connect.field name="units_needed" label="Units needed" type="number" min="1" max="20" value="1" required />
        <x-blood-connect.field name="hospital_name" label="Hospital name" required maxlength="255" />
        <x-blood-connect.select name="district" label="District" :options="$districts" placeholder="Select district" required />
        <x-blood-connect.field name="area" label="Area / location" required maxlength="150" />
        <x-blood-connect.field name="required_date" label="Required date" type="date" :min="today()->toDateString()" :max="today()->addDays(30)->toDateString()" required />
        <x-blood-connect.select name="emergency_level" label="Emergency level" :options="['normal','urgent','critical']" value="normal" required />
        <div class="sm:col-span-2"><label for="message" class="block font-semibold">Short message (optional)</label><textarea id="message" name="message" rows="3" maxlength="2000" class="mt-2 w-full rounded-xl border border-stone-300">{{ old('message') }}</textarea>@error('message')<p class="text-sm text-red-700">{{ $message }}</p>@enderror</div>
        <button class="justify-self-start rounded-xl bg-red-700 px-5 py-3 text-white">Send request to donor</button>
    </form>
</section>
@endsection
