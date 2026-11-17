<x-blood-connect.admin-shell>
    <h1 class="text-2xl font-bold">Verified donation records</h1>
    <div class="flex flex-col gap-4">@forelse($donations as $donation)
        <article class="rounded-2xl border border-stone-200 bg-white p-6"><h2 class="font-semibold">{{ $donation->bloodDonor?->full_name ?? 'Former donor' }} · {{ $donation->units_donated }} unit(s)</h2><p class="mt-2">{{ $donation->donation_date->format('d M Y') }} · {{ $donation->hospital_name }} · {{ ucfirst($donation->status) }}</p><a class="mt-2 inline-block break-all text-sm text-red-800 underline" href="{{ route('admin.blood-connect.requests.show',$donation->bloodRequest) }}">{{ $donation->bloodRequest->request_code }}</a><p class="mt-2 text-sm">Verified by {{ $donation->verifier?->name ?? 'Former administrator' }}</p></article>
    @empty<p>No verified donations recorded.</p>@endforelse</div>
    {{ $donations->links() }}
</x-blood-connect.admin-shell>

