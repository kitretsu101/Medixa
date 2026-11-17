<x-blood-connect.admin-shell>
    <h1 class="text-2xl font-bold">Blood requests</h1>
    <form method="GET" class="flex flex-col items-start gap-4 sm:flex-row sm:items-end">
        <x-blood-connect.select name="status" label="Status" :options="['pending','active','rejected','fulfilled','expired']" :value="request('status')" placeholder="All statuses" />
        <button class="rounded-xl bg-red-700 px-5 py-3 text-white">Filter</button>
    </form>
    <div class="flex flex-col gap-4">@forelse($requests as $bloodRequest)
        <article class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-stone-200 bg-white p-5">
            <div><p class="font-bold">{{ $bloodRequest->blood_group }} · {{ $bloodRequest->hospital_name }}</p><p class="mt-2 text-sm">{{ ucfirst($bloodRequest->status) }} · {{ ucfirst($bloodRequest->emergency_level) }} · {{ $bloodRequest->district }} · {{ $bloodRequest->required_date->format('d M Y') }}</p><p class="mt-2 text-sm text-red-800">{{ $bloodRequest->report_count }} suspicious-request report(s)</p></div>
            <a href="{{ route('admin.blood-connect.requests.show', $bloodRequest) }}" class="break-all text-sm font-semibold text-red-800 underline">{{ $bloodRequest->request_code }}</a>
        </article>
    @empty<p>No requests found.</p>@endforelse</div>
    {{ $requests->links() }}
</x-blood-connect.admin-shell>

