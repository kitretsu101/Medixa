@extends('blood-connect.independent.layout')
@section('title', 'Create donor account')
@section('donor-content')
<section class="rounded-2xl border border-stone-200 bg-white p-6">
    <h1 class="text-2xl font-bold">Create your donor account</h1>
    <p class="mt-2 text-sm">This is a separate donor login. No hospital account or administrator approval is required.</p>
    <form method="POST" action="{{ route('blood-connect.donor.store') }}" class="mt-6 grid gap-5 sm:grid-cols-2">
        @csrf
        <x-blood-connect.field name="full_name" label="Full name" required maxlength="255" />
        <x-blood-connect.select name="blood_group" label="Blood group" :options="$groups" placeholder="Select blood group" required />
        <x-blood-connect.field name="phone" label="Bangladeshi mobile number" type="tel" required maxlength="20" />
        <x-blood-connect.select name="district" label="District" :options="$districts" placeholder="Select district" required />
        <x-blood-connect.field name="area" label="Area / location" required maxlength="255" />
        <x-blood-connect.field name="last_donation_date" label="Last donation date (optional)" type="date" :max="today()->toDateString()" />
        <x-blood-connect.field name="password" label="Password" type="password" required minlength="8" maxlength="72" autocomplete="new-password" />
        <x-blood-connect.field name="password_confirmation" label="Confirm password" type="password" required maxlength="72" autocomplete="new-password" />
        <x-blood-connect.select name="availability_status" label="Availability" :options="['available','temporarily_unavailable','unavailable']" value="available" required />
        <div class="flex flex-col gap-3 sm:col-span-2">
            <input type="hidden" name="public_profile" value="0">
            <label class="flex items-start gap-3"><input type="checkbox" name="public_profile" value="1" @checked(old('public_profile'))><span>Show my name, blood group and location in the public donor directory after phone verification. My phone will not be shown.</span></label>
            <input type="hidden" name="contact_consent" value="0">
            <label class="flex items-start gap-3"><input type="checkbox" name="contact_consent" value="1" @checked(old('contact_consent'))><span>Allow my contact details to be shared with authorized hospital staff for the existing blood-donation coordination process.</span></label>
            <p class="text-sm">SMS delivery is a development stub. In local development, the verification code appears on your donor dashboard and in the development log.</p>
        </div>
        <button class="justify-self-start rounded-xl bg-red-700 px-5 py-3 font-semibold text-white">Register as a donor</button>
    </form>
</section>
@endsection

