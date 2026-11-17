<x-blood-connect.admin-shell>
    <h1 class="text-2xl font-bold">Registered donors</h1>
    <div class="grid gap-4 lg:grid-cols-2">@forelse($donors as $donor)
        <article class="rounded-2xl border border-stone-200 bg-white p-6"><h2 class="font-bold">{{ $donor->full_name }} · {{ $donor->blood_group }}</h2><p class="mt-2 text-sm">{{ $donor->district }} · {{ $donor->area }}</p><p class="mt-2 text-sm">Last donation: {{ $donor->last_donation_date?->format('d M Y') ?? 'Not provided' }}</p><p class="mt-2 text-sm">{{ $donor->contact_consent ? $donor->phone : 'Contact consent withheld' }} · {{ $donor->phone_verified_at ? 'Phone verified' : 'Phone unverified' }}</p>
            <form method="POST" action="{{ route('admin.blood-connect.donors.update', $donor) }}" class="mt-4 flex flex-col gap-3">@csrf @method('PUT')
                <label for="availability-{{ $donor->id }}">Availability</label><select id="availability-{{ $donor->id }}" name="availability_status" class="rounded-xl border border-stone-300 px-3 py-2">@foreach(['available','temporarily_unavailable','unavailable'] as $status)<option value="{{ $status }}" @selected($donor->availability_status === $status)>{{ str_replace('_',' ',ucfirst($status)) }}</option>@endforeach</select>
                <button class="self-start rounded-xl bg-stone-800 px-4 py-2 text-white">Update availability</button>
            </form>
        </article>
    @empty<p>No registered donors yet.</p>@endforelse</div>
    {{ $donors->links() }}
</x-blood-connect.admin-shell>

