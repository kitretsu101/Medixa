<article class="flex flex-col gap-4 rounded-2xl border border-rose-200 bg-white p-5 shadow-sm transition-shadow sm:p-7 hover:shadow-md">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <span class="rounded-xl bg-red-50 px-4 py-2 text-2xl font-bold text-red-800">{{ $bloodRequest->blood_group }}</span>
        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $bloodRequest->emergency_level === 'critical' ? 'bg-[#DC2626] text-white' : ($bloodRequest->emergency_level === 'urgent' ? 'bg-rose-100 text-red-800 ring-1 ring-rose-200' : 'bg-stone-100 text-stone-700') }}">{{ ucfirst($bloodRequest->emergency_level) }}</span>
    </div>
    <h3 class="break-words font-semibold text-stone-900">{{ $bloodRequest->hospital_name }}</h3>
    <p class="text-sm text-stone-600">{{ $bloodRequest->district }} · {{ $bloodRequest->area }}</p>
    <p class="text-sm text-stone-600">{{ $bloodRequest->units_required - $bloodRequest->units_fulfilled }} unit(s) remaining · Needed {{ $bloodRequest->required_date->format('d M Y') }}</p>
    <p class="break-all text-xs text-stone-500">{{ $bloodRequest->request_code }} · Active</p>
    <a class="mt-auto text-sm font-semibold text-red-800 underline" href="{{ route('blood-connect.requests.show', $bloodRequest) }}">View request and contact options</a>
</article>

