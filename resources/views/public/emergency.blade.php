@extends('layouts.public')

@section('title', 'Emergency Request — Medixa')

@section('content')
<section class="mx-auto max-w-xl px-4 py-12">
    <h1 class="font-display text-3xl font-semibold text-stone-800">Emergency Request</h1>
    <p class="mt-3 text-sm text-stone-600">Send a request to the reception desk for manual follow-up. Submitting this form does not dispatch an ambulance or reserve a bed.</p>

    <form method="POST" action="{{ route('emergency.store') }}" class="mt-6 space-y-5 rounded-2xl border border-stone-200 bg-white p-6">
        @csrf
        <div>
            <label for="patient_name" class="block text-sm font-semibold">Patient name (optional)</label>
            <input id="patient_name" name="patient_name" value="{{ old('patient_name') }}" maxlength="255" autocomplete="name" class="mt-1 block w-full rounded-lg border-stone-300">
            @error('patient_name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="contact_phone" class="block text-sm font-semibold">Contact phone</label>
            <input id="contact_phone" name="contact_phone" type="tel" value="{{ old('contact_phone') }}" required maxlength="30" autocomplete="tel" class="mt-1 block w-full rounded-lg border-stone-300">
            @error('contact_phone')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="location" class="block text-sm font-semibold">Location</label>
            <input id="location" name="location" value="{{ old('location') }}" required maxlength="255" class="mt-1 block w-full rounded-lg border-stone-300">
            @error('location')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="issue_type" class="block text-sm font-semibold">Issue type</label>
            <select id="issue_type" name="issue_type" required class="mt-1 block w-full rounded-lg border-stone-300">
                <option value="">Select an issue type</option>
                @foreach($issueTypes as $issueType)
                    <option value="{{ $issueType }}" @selected(old('issue_type') === $issueType)>{{ ucfirst($issueType) }}</option>
                @endforeach
            </select>
            @error('issue_type')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="rounded-full bg-red-700 px-6 py-3 text-sm font-semibold text-white hover:bg-red-800">Send request</button>
    </form>
</section>
@endsection
