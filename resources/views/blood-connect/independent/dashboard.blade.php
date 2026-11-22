@extends('blood-connect.independent.layout')
@section('title', 'Donor dashboard')
@section('donor-content')
<section class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm transition-shadow sm:p-7 hover:shadow-md">
    <p class="mb-2 text-xs font-bold uppercase tracking-widest text-red-700">Your donor space</p><h1 class="break-words text-2xl font-bold text-red-950 sm:text-3xl">Welcome, {{ $donor->full_name }}</h1>
    <p class="mt-2">{{ $donor->blood_group }} · {{ $donor->district }} · {{ $donor->area }}</p>
    <p class="mt-3"><span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $donor->availability_status === 'available' ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200' : 'bg-stone-100 text-stone-700' }}">{{ ucfirst(str_replace('_', ' ', $donor->availability_status)) }}</span></p>
    <p class="mt-2">{{ $donor->phone_verified_at ? 'Phone verified' : 'Phone not verified — your profile is not publicly listed.' }}</p>
    <a class="mt-4 inline-block font-semibold text-red-800 underline" href="{{ route('blood-connect.donor.requests') }}">View your requests ({{ $pendingCount }} pending)</a>
</section>
@if(!$donor->phone_verified_at)
<section class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
    <h2 class="text-xl font-bold">Verify your phone</h2>
    @if(app()->environment(['local', 'testing']) && session('dev_otp'))
        <p class="mt-3 font-semibold">DEVELOPMENT SMS STUB — verification code: {{ session('dev_otp') }}</p>
    @endif
    <p class="mt-2 text-sm">Codes expire after ten minutes and allow five attempts. A real SMS gateway is not connected.</p>
    <form method="POST" action="{{ route('blood-connect.donor.verify-phone') }}" class="mt-4 flex flex-col gap-3 sm:max-w-sm">
        @csrf
        <x-blood-connect.field name="code" label="Six-digit verification code" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" required />
        <button class="self-start rounded-xl bg-[#DC2626] px-4 py-2 font-semibold text-white shadow-sm transition hover:bg-[#B91C1C] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Verify phone</button>
    </form>
    <form method="POST" action="{{ route('blood-connect.donor.resend-otp') }}" class="mt-4">@csrf<button class="font-semibold text-red-800 underline">Resend code (once per minute)</button></form>
</section>
@endif
<section class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm transition-shadow sm:p-7 hover:shadow-md">
    <h2 class="text-xl font-bold">Directory preferences</h2>
    <form method="POST" action="{{ route('blood-connect.donor.preferences') }}" class="mt-4 flex max-w-xl flex-col gap-4">
        @csrf
        <x-blood-connect.select name="availability_status" label="Availability" :options="['available','temporarily_unavailable','unavailable']" :value="$donor->availability_status" required />
        <input type="hidden" name="public_profile" value="0">
        <label class="flex items-start gap-3 rounded-xl bg-rose-50 p-4 text-sm leading-relaxed"><input class="mt-1 shrink-0 rounded border-rose-300 text-red-600 focus:ring-red-500" type="checkbox" name="public_profile" value="1" @checked(old('public_profile', $donor->public_profile))>Show my profile in the public directory after verification.</label>
        <button class="self-start rounded-xl bg-[#DC2626] px-4 py-2 font-semibold text-white shadow-sm transition hover:bg-[#B91C1C] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Save preferences</button>
    </form>
</section>
@endsection
