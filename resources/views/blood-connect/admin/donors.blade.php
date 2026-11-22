<x-blood-connect.admin-shell>
    <div class="rounded-2xl border border-rose-200 bg-linear-to-br from-rose-50 to-white p-6"><p class="mb-2 text-xs font-bold uppercase tracking-widest text-red-700">Donor community</p><h1 class="text-2xl font-bold text-red-950">Registered donors</h1></div>
    <div class="grid gap-4 lg:grid-cols-2">@forelse($donors as $donor)
        <article class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm transition-shadow sm:p-7 hover:shadow-md">
            <h2 class="break-words text-lg font-bold text-red-950">{{ $donor->full_name }} · {{ $donor->blood_group }}</h2>
            <p class="mt-2 text-sm">{{ $donor->district }} · {{ $donor->area }}</p>
            <p class="mt-2 text-sm"><span class="inline-flex rounded-full bg-rose-50 px-3 py-1 font-semibold text-red-800">Availability: {{ str_replace('_', ' ', $donor->availability_status) }}</span></p>
            <p class="mt-2 text-sm">{{ $donor->phone_verified_at ? 'Phone verified' : 'Phone unverified' }}</p>
            <p class="mt-2 text-sm">Registered: {{ $donor->created_at->format('d M Y') }}</p>
        </article>
    @empty<p>No registered donors yet.</p>@endforelse</div>
    {{ $donors->links() }}
</x-blood-connect.admin-shell>
