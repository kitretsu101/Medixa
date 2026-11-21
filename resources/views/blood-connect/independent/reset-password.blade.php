@extends('blood-connect.independent.layout')
@section('title', 'Reset donor password')
@section('donor-content')
<section class="max-w-xl rounded-2xl border border-stone-200 bg-white p-6">
    <h1 class="text-2xl font-bold">Reset donor password</h1>
    <form method="POST" action="{{ route('blood-connect.donor.password.update', $donorId) }}" class="mt-6 flex flex-col gap-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        @error('token')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
        <x-blood-connect.field name="password" label="New password" type="password" required minlength="8" maxlength="72" autocomplete="new-password" />
        <x-blood-connect.field name="password_confirmation" label="Confirm new password" type="password" required maxlength="72" autocomplete="new-password" />
        <button class="self-start rounded-xl bg-red-700 px-4 py-2 text-white">Reset donor password</button>
    </form>
</section>
@endsection

