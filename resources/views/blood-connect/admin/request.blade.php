<x-blood-connect.admin-shell>
    <section class="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-white p-6">
        <h1 class="break-all text-2xl font-bold">{{ $bloodRequest->request_code }}</h1>
        <p class="font-semibold">{{ $bloodRequest->blood_group }} · {{ ucfirst($bloodRequest->status) }} · {{ ucfirst($bloodRequest->emergency_level) }}</p>
        <p>Patient (private): {{ $bloodRequest->patient_name }}</p><p>Contact (private): {{ $bloodRequest->contact_name }} · {{ $bloodRequest->contact_number }}</p>
        <p>{{ $bloodRequest->hospital_name }} · {{ $bloodRequest->district }} · {{ $bloodRequest->area }}</p>
        <p>Required {{ $bloodRequest->required_date->format('d M Y') }} · {{ $bloodRequest->units_fulfilled }} / {{ $bloodRequest->units_required }} units verified</p>
        <p>Public contact consent: {{ $bloodRequest->contact_consent ? 'Yes' : 'No' }}</p>
        <p class="whitespace-pre-wrap">{{ $bloodRequest->description }}</p>
    </section>
    @if(in_array($bloodRequest->status, ['pending','active'], true))
    <section class="rounded-2xl border border-stone-200 bg-white p-6"><h2 class="mb-4 text-xl font-bold">Review request</h2>
        <form method="POST" action="{{ route('admin.blood-connect.requests.update', $bloodRequest) }}" class="flex flex-col gap-4">@csrf @method('PUT')
            <x-blood-connect.select name="status" label="New status" :options="$bloodRequest->status === 'pending' ? ['active','rejected'] : ['rejected','expired','fulfilled']" required />
            <label for="notes">Review notes</label><textarea id="notes" name="notes" required minlength="5" maxlength="2000" class="rounded-xl border border-stone-300 px-3 py-2">{{ old('notes') }}</textarea>
            <button class="self-start rounded-xl bg-red-700 px-5 py-3 text-white">Save review</button>
        </form>
    </section>
    @endif
    <section class="rounded-2xl border border-stone-200 bg-white p-6"><h2 class="mb-4 text-xl font-bold">Interested donors</h2>
        @forelse($bloodRequest->interests as $interest)
            <p class="mb-3">{{ $interest->bloodDonor->full_name }} · {{ $interest->bloodDonor->blood_group }} · {{ $interest->bloodDonor->district }}
                @if($interest->bloodDonor->contact_consent) · {{ $interest->bloodDonor->phone }} @else · Contact consent withheld @endif
            </p>
        @empty<p>No donor interest yet.</p>@endforelse
    </section>
    @if($bloodRequest->isActive())
    <section class="rounded-2xl border border-stone-200 bg-white p-6"><h2 class="mb-4 text-xl font-bold">Record completed donation</h2>
        <p class="mb-4 text-sm">Only record donations confirmed by qualified medical staff. Interest alone is not proof of donation.</p>
        <form method="POST" action="{{ route('admin.blood-connect.donations.store', $bloodRequest) }}" class="grid gap-4 sm:grid-cols-2">@csrf
            <div><label for="blood_donor_id">Interested donor</label><select id="blood_donor_id" name="blood_donor_id" required class="mt-2 block w-full rounded-xl border border-stone-300 px-3 py-2"><option value="">Select donor</option>@foreach($bloodRequest->interests as $interest)<option value="{{ $interest->blood_donor_id }}">{{ $interest->bloodDonor->full_name }} · {{ $interest->bloodDonor->blood_group }}</option>@endforeach</select></div>
            <x-blood-connect.field name="donation_date" label="Donation date" type="date" :min="$bloodRequest->created_at->toDateString()" :max="today()->toDateString()" required />
            <x-blood-connect.field name="units_donated" label="Verified units donated" type="number" min="1" :max="$bloodRequest->units_required - $bloodRequest->units_fulfilled" required />
            <button class="self-end justify-self-start rounded-xl bg-red-700 px-5 py-3 text-white">Record verified donation</button>
        </form>
    </section>
    @endif
    @if($matches)
    <section class="rounded-2xl border border-stone-200 bg-white p-6"><h2 class="mb-4 text-xl font-bold">Preliminary donor matches</h2><p class="mb-4 text-sm">Available donors in this district who permit profile and contact sharing. Clinical compatibility and eligibility still require confirmation.</p>
        @forelse($matches as $donor)<p class="mb-3">{{ $donor->full_name }} · {{ $donor->blood_group }} · {{ $donor->area }} · {{ $donor->phone }}</p>@empty<p>No consenting available matches in this district.</p>@endforelse
        {{ $matches->links() }}
    </section>
    @endif
    <section class="rounded-2xl border border-stone-200 bg-white p-6"><h2 class="mb-4 text-xl font-bold">Request history and reports</h2>
        @foreach($bloodRequest->events->sortByDesc('id') as $event)<article class="border-b border-stone-100 py-3"><p class="font-semibold">{{ ucfirst(str_replace('_',' ',$event->action)) }} · {{ $event->created_at->format('d M Y H:i') }} · {{ $event->user?->name ?? 'Public visitor' }}</p><p class="mt-2 whitespace-pre-wrap text-sm">{{ $event->notes }}</p></article>@endforeach
    </section>
</x-blood-connect.admin-shell>

