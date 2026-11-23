@if($approvedAppointments->isNotEmpty())
<section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
    <div class="border-b border-gray-100 p-5"><h3 class="text-lg font-bold text-gray-900">Approved appointments</h3><p class="text-sm text-gray-500">Requests approved by the hospital receptionist.</p></div>
    <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-gray-50 text-gray-600"><tr><th class="px-6 py-3">Patient</th><th class="px-6 py-3">Hospital</th><th class="px-6 py-3">Date / serial</th><th class="px-6 py-3">Reason</th><th class="px-6 py-3">Status</th></tr></thead><tbody class="divide-y divide-gray-100">
    @foreach($approvedAppointments as $approved)
        <tr><td class="px-6 py-4">{{ $approved->patient?->name }}</td><td class="px-6 py-4">{{ $approved->hospital?->name }}</td><td class="px-6 py-4">{{ $approved->appointment_date?->format('d M Y') }} · #{{ $approved->serial_number }}</td><td class="px-6 py-4">{{ $approved->reason ?? 'Not specified' }}</td><td class="px-6 py-4"><span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Approved</span></td></tr>
    @endforeach
    </tbody></table></div>
    <div class="p-4">{{ $approvedAppointments->links() }}</div>
</section>
@endif
