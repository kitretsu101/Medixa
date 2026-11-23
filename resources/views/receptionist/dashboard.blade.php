<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Today's desk</h2>
                <p class="text-sm text-slate-500">Check patients in and see the queue. Medical records stay with the doctor.</p>
            </div>
            @if(auth()->user()->hospital_id)<a href="{{ route('receptionist.walk-in') }}" class="px-4 py-2 rounded-lg bg-teal-700 text-white text-sm font-semibold">Walk-in</a>@endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4">
            @if(session('status'))
                <div class="mb-4 rounded-xl bg-teal-50 border border-teal-200 p-4 text-sm text-teal-800">{{ session('status') }}</div>
            @endif
            @if(auth()->user()->hospital_id === null)
                <p class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">No hospital is assigned to your account. Contact an administrator. Appointment management is unavailable.</p>
            @else
                <div class="mb-5 space-y-4"><div class="flex flex-wrap items-center justify-between gap-3"><h3 class="text-lg font-bold text-slate-900">{{ auth()->user()->hospital?->name }} · Pending requests</h3><a href="{{ route('receptionist.appointments.index') }}" class="font-semibold text-teal-700">All hospital appointments</a></div>
                    @error('appointment')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                    @include('receptionist.partials.appointment-requests')
                </div>
            @endif
            <div class="bg-white rounded-2xl border border-slate-200 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Serial</th>
                            <th class="px-4 py-3">Patient</th>
                            <th class="px-4 py-3">Doctor</th>
                            <th class="px-4 py-3">Priority</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($visits as $visit)
                            <tr>
                                <td class="px-4 py-3 font-mono">#{{ $visit->serial_number }}</td>
                                <td class="px-4 py-3">{{ $visit->patient?->name }}</td>
                                <td class="px-4 py-3">{{ $visit->doctor?->name }}</td>
                                <td class="px-4 py-3">{{ str_replace('_', ' ', $visit->priority_type) }}</td>
                                <td class="px-4 py-3">{{ str_replace('_', ' ', $visit->status) }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if(in_array($visit->status, ['confirmed', 'approved'], true))
                                        <form method="POST" action="{{ route('receptionist.check-in', $visit) }}">
                                            @csrf
                                            <button class="text-teal-700 font-semibold">Check in</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No appointments are booked for today.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
