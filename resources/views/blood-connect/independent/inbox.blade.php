@extends('blood-connect.independent.layout')
@section('title', 'My donor requests')
@section('donor-content')
<h1 class="text-3xl font-bold">Requests sent to you</h1>
<form method="GET" action="{{ route('blood-connect.donor.requests') }}" class="flex flex-wrap items-end gap-4">
    <x-blood-connect.select name="status" label="Status" :options="['pending','contacted','closed']" :value="request('status')" placeholder="All statuses" />
    <button class="rounded-xl bg-stone-800 px-4 py-2 text-white">Filter</button>
</form>
@forelse($contactRequests as $contactRequest)
<article class="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-white p-6">
    <h2 class="text-xl font-bold">{{ $contactRequest->requester_name ?? 'Requester' }} · {{ ucfirst($contactRequest->status) }}</h2>
    <p>Phone: <a class="text-red-800 underline" href="tel:{{ $contactRequest->requester_phone }}">{{ $contactRequest->requester_phone }}</a></p>
    <p>{{ $contactRequest->blood_group_needed }} · {{ $contactRequest->units_needed }} unit(s) · {{ ucfirst($contactRequest->emergency_level) }}</p>
    <p>{{ $contactRequest->hospital_name }} · {{ $contactRequest->location }}</p>
    <p>Required: {{ $contactRequest->required_date->format('d M Y') }} · Received: {{ $contactRequest->created_at->format('d M Y H:i') }}</p>
    <p class="whitespace-pre-line">{{ $contactRequest->message }}</p>
    @if($contactRequest->status !== 'closed')
    <form method="POST" action="{{ route('blood-connect.donor.requests.status', $contactRequest) }}">
        @csrf
        <input type="hidden" name="status" value="{{ $contactRequest->status === 'pending' ? 'contacted' : 'closed' }}">
        <button class="rounded-xl bg-red-700 px-4 py-2 text-white">{{ $contactRequest->status === 'pending' ? 'Mark contacted' : 'Close request' }}</button>
    </form>
    @endif
</article>
@empty
<p>No requests in this view.</p>
@endforelse
{{ $contactRequests->links() }}
@endsection
