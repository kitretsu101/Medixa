@extends('blood-connect.independent.layout')
@section('title', 'Donor login')
@section('donor-content')
<section class="max-w-xl rounded-2xl border border-stone-200 bg-white p-6">
    <h1 class="text-2xl font-bold">Donor login</h1>
    <form method="POST" action="{{ route('blood-connect.donor.login.store') }}" class="mt-6 flex flex-col gap-5">
        @csrf
        <x-blood-connect.field name="phone" label="Donor mobile number" type="tel" required maxlength="20" autocomplete="username" />
        <x-blood-connect.field name="password" label="Password" type="password" required maxlength="72" autocomplete="current-password" />
        <label class="flex items-center gap-3"><input type="checkbox" name="remember" value="1">Remember my donor login</label>
        <button class="self-start rounded-xl bg-red-700 px-5 py-3 font-semibold text-white">Sign in as donor</button>
    </form>
    <p class="mt-4 text-sm">Historical donors should use the one-time reset link supplied by the project owner. Donor and hospital credentials are unrelated.</p>
</section>
@endsection

