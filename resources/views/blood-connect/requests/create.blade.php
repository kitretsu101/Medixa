@extends('blood-connect.layout')
@section('blood-content')
<div><h1 class="font-display text-3xl font-semibold text-stone-900">Request blood</h1><p class="mt-3 text-stone-600">No account is required. An administrator reviews every request before publication. Patient names and descriptions remain private.</p></div>
<form method="POST" action="{{ route('blood-connect.requests.store') }}" class="grid gap-5 rounded-2xl border border-rose-200 bg-white p-5 shadow-sm transition-shadow sm:p-7 hover:shadow-md sm:grid-cols-2">
    @csrf
    <x-blood-connect.field name="patient_name" label="Patient name (private)" required maxlength="255" />
    <x-blood-connect.field name="contact_name" label="Contact person name (private)" required maxlength="255" />
    <x-blood-connect.select name="blood_group" label="Blood group" :options="$groups" placeholder="Select blood group" required />
    <x-blood-connect.field name="units_required" label="Required units" type="number" min="1" max="20" required />
    <x-blood-connect.field name="hospital_name" label="Hospital name" required maxlength="255" />
    <x-blood-connect.field name="district" label="District" required maxlength="100" />
    <x-blood-connect.field name="area" label="Area / location" required maxlength="255" />
    <x-blood-connect.field name="required_date" label="Required date" type="date" :min="today()->toDateString()" :max="today()->addDays(30)->toDateString()" required />
    <x-blood-connect.select name="emergency_level" label="Emergency level" :options="['normal','urgent','critical']" required />
    <x-blood-connect.field name="contact_number" label="Bangladeshi mobile number" type="tel" placeholder="01712345678 or +8801712345678" required maxlength="20" />
    <div class="sm:col-span-2"><label for="description" class="block text-sm font-semibold">Additional description (private, optional)</label><textarea id="description" name="description" rows="3" maxlength="2000" class="mt-2 block min-w-0 w-full rounded-xl border border-rose-200 px-3 py-2 shadow-sm transition focus:border-red-500 focus:ring-2 focus:ring-red-500/20">{{ old('description') }}</textarea></div>
    <div class="sm:col-span-2"><input type="hidden" name="contact_consent" value="0"><label class="flex items-start gap-3"><input type="checkbox" name="contact_consent" value="1" @checked(old('contact_consent')) class="mt-1 rounded border-rose-200 text-red-700"><span class="text-sm">I consent to publishing my contact number on this request. Without consent, no public contact number will be shown.</span></label></div>
    <div hidden aria-hidden="true"><label for="website">Leave this empty</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
    <div class="sm:col-span-2"><button class="rounded-full bg-[#DC2626] px-6 py-3 font-semibold text-white hover:bg-[#B91C1C]" x-data="{ submitting: false }" x-on:click="if ($el.form.checkValidity()) { submitting = true }" :aria-busy="submitting" x-text="submitting ? 'Sending request…' : 'Submit for review'">Submit for review</button></div>
</form>
@endsection

