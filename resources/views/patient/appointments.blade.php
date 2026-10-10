<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Appointments</h2>
                <p class="text-sm text-slate-500">Your OPD bookings and serial numbers.</p>
            </div>
            <a href="{{ route('patient.appointments.create') }}" class="inline-flex items-center px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg">Book</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if(session('status'))
                <div class="rounded-xl bg-teal-50 border border-teal-200 p-4 text-sm text-teal-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Doctor</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Your serial</th>
                            <th class="px-6 py-3">Now serving</th>
                            <th class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($appointments as $appointment)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900"><x-doctor-name :name="$appointment->doctor?->name" /></div>
                                    <div class="text-xs text-slate-500">{{ $appointment->doctor?->doctorProfile?->department?->name }}</div>
                                </td>
                                <td class="px-6 py-4">{{ $appointment->appointment_date?->format('M d, Y') }}</td>
                                <td class="px-6 py-4 font-mono font-bold">#{{ $appointment->serial_number }}</td>
                                <td class="px-6 py-4 font-mono font-bold text-teal-700">
                                    @if(in_array($appointment->status, ['pending', 'confirmed'], true) && $appointment->appointment_date?->isToday())
                                        #{{ (int) ($appointment->doctor?->liveQueue?->current_running_serial ?? 0) }}
                                    @else
                                        <span class="font-sans font-normal text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="capitalize">{{ str_replace('_', ' ', $appointment->status) }}</div>
                                    <div class="text-xs text-slate-500">{{ str_replace('_', ' ', $appointment->priority_type ?? 'normal') }}</div>
                                    @if(in_array($appointment->status, ['pending', 'confirmed'], true) && $appointment->appointment_date?->isToday())
                                        <form method="POST" action="{{ route('patient.appointments.check-in', $appointment) }}" class="mt-2">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold text-teal-700">Check in</button>
                                        </form>
                                    @endif
                                    @if($appointment->status === 'completed' && $appointment->feedback === null)
                                        <form method="POST" action="{{ route('patient.appointments.feedback', $appointment) }}" class="mt-2 space-y-1">
                                            @csrf
                                            <label class="sr-only" for="waiting-{{ $appointment->id }}">Waiting time rating</label>
                                            <input id="waiting-{{ $appointment->id }}" name="waiting_rating" type="number" min="1" max="5" required placeholder="Wait 1-5" class="w-24 rounded border-slate-300 text-xs">
                                            <label class="sr-only" for="doctor-{{ $appointment->id }}">Doctor rating</label>
                                            <input id="doctor-{{ $appointment->id }}" name="doctor_rating" type="number" min="1" max="5" required placeholder="Doctor 1-5" class="w-24 rounded border-slate-300 text-xs">
                                            <label class="sr-only" for="system-{{ $appointment->id }}">System rating</label>
                                            <input id="system-{{ $appointment->id }}" name="system_rating" type="number" min="1" max="5" required placeholder="System 1-5" class="w-24 rounded border-slate-300 text-xs">
                                            <button type="submit" class="block text-xs font-semibold text-teal-700">Send feedback</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-400">You don't have any appointments yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $appointments->links() }}
        </div>
    </div>
</x-app-layout>
