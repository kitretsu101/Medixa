<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
    <table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Patient</th><th class="p-4">Doctor</th><th class="p-4">Hospital / date</th><th class="p-4">Status</th><th class="p-4">Actions</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($requests as $visit)
            <tr>
                <td class="p-4">{{ $visit->patient?->name }}<p class="mt-1 text-xs text-slate-500">{{ $visit->reason }}</p></td>
                <td class="p-4">{{ $visit->doctor?->name }}<p class="text-xs text-slate-500">Serial #{{ $visit->serial_number }}</p></td>
                <td class="p-4">{{ $visit->hospital?->name }}<p class="text-xs text-slate-500">{{ $visit->appointment_date?->format('d M Y') }}</p></td>
                <td class="p-4"><span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $visit->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($visit->status === 'rejected' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">{{ ucfirst(str_replace('_', ' ', $visit->status)) }}</span></td>
                <td class="p-4"><div class="flex flex-wrap gap-3">
                    <a href="{{ route('receptionist.appointments.show', $visit) }}" class="font-semibold text-teal-700 hover:text-teal-900">Details</a>
                    @if($visit->status === 'pending')
                    <form method="POST" action="{{ route('receptionist.appointments.approve', $visit) }}">@csrf<button class="font-semibold text-teal-700 hover:text-teal-900">Approve</button></form>
                    <form method="POST" action="{{ route('receptionist.appointments.reject', $visit) }}">@csrf<button class="font-semibold text-rose-700 hover:text-rose-900">Reject</button></form>
                    @endif
                </div></td>
            </tr>
        @empty<tr><td colspan="5" class="p-6 text-center text-slate-500">No appointment requests for this hospital.</td></tr>@endforelse
        </tbody>
    </table>
</div>
