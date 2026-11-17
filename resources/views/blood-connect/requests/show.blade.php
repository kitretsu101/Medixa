@extends('blood-connect.layout')
@section('blood-content')
<section class="flex flex-col gap-5 rounded-2xl border border-stone-200 bg-white p-6 sm:p-8">
    <div class="flex flex-wrap items-center gap-4"><span class="rounded-xl bg-red-50 px-5 py-3 text-3xl font-bold text-red-800">{{ $bloodRequest->blood_group }}</span><h1 class="text-2xl font-semibold">{{ $bloodRequest->hospital_name }}</h1></div>
    <p class="break-all text-sm text-stone-500">{{ $bloodRequest->request_code }}</p>
    <p>{{ $bloodRequest->district }} · {{ $bloodRequest->area }}</p>
    <p>Needed {{ $bloodRequest->required_date->format('d M Y') }} · {{ ucfirst($bloodRequest->emergency_level) }} · Active</p>
    <p>{{ $bloodRequest->units_fulfilled }} of {{ $bloodRequest->units_required }} unit(s) verified.</p>
    @if($bloodRequest->contact_consent)
        <a class="font-semibold text-red-800 underline" href="tel:{{ $bloodRequest->contact_number }}">Contact requester: {{ $bloodRequest->contact_number }}</a>
    @else
        <p class="text-sm text-stone-600">The requester has not consented to public contact details.</p>
    @endif
    @auth
        <form method="POST" action="{{ route('blood-connect.requests.interest', $bloodRequest) }}">@csrf<button class="rounded-full bg-red-700 px-6 py-3 font-semibold text-white">I am interested in donating</button></form>
    @else
        <a class="font-semibold text-red-800 underline" href="{{ route('blood-connect.donor.register') }}">Sign in to register as a donor and express interest</a>
    @endauth
    <p class="text-sm text-stone-600">Your interest does not fulfill this request. Only administrator-verified completed donations count.</p>
</section>
<details class="rounded-2xl border border-stone-200 bg-white p-6">
    <summary class="cursor-pointer font-semibold">Report a suspicious or fake request</summary>
    <form method="POST" action="{{ route('blood-connect.requests.report', $bloodRequest) }}" class="mt-4 flex flex-col gap-4">@csrf
        <label for="reason">Reason (at least 10 characters)</label><textarea id="reason" name="reason" required minlength="10" maxlength="1000" rows="3" class="rounded-xl border border-stone-300 px-3 py-2">{{ old('reason') }}</textarea>
        <div hidden><input name="website" tabindex="-1" autocomplete="off" aria-label="Leave empty"></div>
        <button class="self-start rounded-xl bg-stone-800 px-5 py-3 text-white">Send report to administrators</button>
    </form>
</details>
@endsection

