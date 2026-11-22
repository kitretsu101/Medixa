<x-blood-connect.admin-shell>
    <h1 class="text-2xl font-bold">Blood requests</h1>
    <form method="GET" class="flex flex-col items-start gap-4 sm:flex-row sm:items-end">
        <x-blood-connect.select name="status" label="Status" :options="['pending','active','rejected','fulfilled','expired']" :value="request('status')" placeholder="All statuses" />
        <button class="rounded-xl bg-[#DC2626] px-5 py-3 font-semibold text-white transition hover:bg-[#B91C1C]">Filter</button>
    </form>
    <div class="flex flex-col gap-4">@forelse($requests as $bloodRequest)
        <article class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-rose-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md">
            <div><p class="font-bold">{{ $bloodRequest->blood_group }} · {{ $bloodRequest->hospital_name }}</p><p class="mt-2 text-sm"><span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $bloodRequest->status === 'fulfilled' ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200' : 'bg-rose-50 text-red-800 ring-1 ring-rose-200' }}">{{ ucfirst($bloodRequest->status) }}</span> · <span class="inline-flex rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-red-800">{{ ucfirst($bloodRequest->emergency_level) }}</span> · {{ $bloodRequest->district }} · {{ $bloodRequest->required_date->format('d M Y') }}</p><p class="mt-2 text-sm text-red-800">{{ $bloodRequest->report_count }} suspicious-request report(s)</p></div>
            <a href="{{ route('admin.blood-connect.requests.show', $bloodRequest) }}" class="break-all text-sm font-semibold text-red-800 underline">{{ $bloodRequest->request_code }}</a>
        </article>
    @empty<p>No requests found.</p>@endforelse</div>
    {{ $requests->links() }}
</x-blood-connect.admin-shell>

