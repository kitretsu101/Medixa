@extends('blood-connect.layout')
@section('blood-content')
<h1 class="text-3xl font-semibold">My donor profile</h1>
<form method="POST" action="{{ route('blood-connect.donor.update') }}" class="grid gap-5 rounded-2xl border border-stone-200 bg-white p-6 sm:grid-cols-2">
    @csrf @method('PUT')
    @include('blood-connect.donor.fields')
    <button class="justify-self-start rounded-full bg-red-700 px-6 py-3 font-semibold text-white">Save profile</button>
</form>
<section class="rounded-2xl border border-stone-200 bg-white p-6">
    <h2 class="text-xl font-semibold">Change availability</h2>
    <form method="POST" action="{{ route('blood-connect.donor.availability') }}" class="mt-4 flex flex-col items-start gap-4 sm:flex-row sm:items-end">@csrf @method('PUT')
        <x-blood-connect.select name="availability_status" label="Availability status" :options="['available','temporarily_unavailable','unavailable']" :value="$donor->availability_status" required id="quick-availability" />
        <button class="rounded-xl bg-stone-800 px-5 py-3 text-white">Update availability</button>
    </form>
</section>
<section><h2 class="mb-4 text-xl font-semibold">My donation history</h2>
    <div class="flex flex-col gap-3">@forelse($donations as $donation)
        <article class="rounded-2xl border border-stone-200 bg-white p-5"><p>{{ $donation->donation_date->format('d M Y') }} · {{ $donation->units_donated }} unit(s) · {{ $donation->hospital_name }}</p><p class="mt-2 break-all text-sm">{{ $donation->bloodRequest->request_code }} · {{ ucfirst($donation->status) }}</p></article>
    @empty<p>No verified donations recorded yet.</p>@endforelse</div>
    <div class="mt-4">{{ $donations->links() }}</div>
</section>
@endsection

