@extends('blood-connect.layout')
@section('blood-content')
<h1 class="text-3xl font-semibold">Become a donor</h1>
<form method="POST" action="{{ route('blood-connect.donor.store') }}" class="grid gap-5 rounded-2xl border border-stone-200 bg-white p-6 sm:grid-cols-2">
    @csrf
    @include('blood-connect.donor.fields')
    <button class="justify-self-start rounded-full bg-red-700 px-6 py-3 font-semibold text-white">Register donor profile</button>
</form>
@endsection

