<x-blood-connect.admin-shell>
    <h1 class="text-2xl font-bold">Registered donors</h1>
    <div class="grid gap-4 lg:grid-cols-2">@forelse($donors as $donor)
        <article class="rounded-2xl border border-stone-200 bg-white p-6">
            <h2 class="font-bold">{{ $donor->full_name }} · {{ $donor->blood_group }}</h2>
            <p class="mt-2 text-sm">{{ $donor->district }} · {{ $donor->area }}</p>
            <p class="mt-2 text-sm">Availability: {{ str_replace('_', ' ', $donor->availability_status) }}</p>
            <p class="mt-2 text-sm">{{ $donor->phone_verified_at ? 'Phone verified' : 'Phone unverified' }}</p>
            <p class="mt-2 text-sm">Registered: {{ $donor->created_at->format('d M Y') }}</p>
        </article>
    @empty<p>No registered donors yet.</p>@endforelse</div>
    {{ $donors->links() }}
</x-blood-connect.admin-shell>
