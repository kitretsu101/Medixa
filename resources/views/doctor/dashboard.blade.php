<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    Doctor Workspace & Clinical Console
                </h2>
                <p class="text-sm text-gray-500 mt-1">Welcome back, Dr. {{ Auth::user()->name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                    <span class="w-2 h-2 mr-1.5 bg-emerald-500 rounded-full animate-pulse"></span> Doctor Mode Active
                </span>
                <a href="{{ route('doctor.pending-reviews') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-medium text-xs text-white uppercase tracking-wider hover:bg-indigo-700 transition">
                    Review Lab Reports ({{ $pendingReportReviews }})
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm flex items-center gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Workload Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Stat 1: Total Patients Seen -->
                <div class="bg-white overflow-hidden shadow-sm rounded-xl p-6 border border-gray-100 relative">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Patients Seen</p>
                            <h3 class="text-3xl font-extrabold text-gray-900 mt-2">{{ $totalPatientsSeen }}</h3>
                            <p class="text-xs text-emerald-600 font-medium mt-1">Completed consultations & history</p>
                        </div>
                        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Stat 2: Pending Appointments -->
                <div class="bg-white overflow-hidden shadow-sm rounded-xl p-6 border border-gray-100 relative">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Pending Appointments</p>
                            <h3 class="text-3xl font-extrabold text-amber-600 mt-2">{{ $pendingAppointments }}</h3>
                            <p class="text-xs text-gray-500 mt-1">Patients awaiting OPD consultation</p>
                        </div>
                        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Stat 3: Pending Lab Report Reviews -->
                <div class="bg-white overflow-hidden shadow-sm rounded-xl p-6 border border-gray-100 relative">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Pending Report Reviews</p>
                            <h3 class="text-3xl font-extrabold text-indigo-600 mt-2">{{ $pendingReportReviews }}</h3>
                            <p class="text-xs text-indigo-600 font-medium mt-1">Requires clinical evaluation</p>
                        </div>
                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent OPD Appointments Section -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Recent OPD Consultations</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Patients booked or seen under your schedule</p>
                    </div>
                    <a href="{{ route('doctor.patients.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition">
                        View All Linked Patients &rarr;
                    </a>
                </div>

                @if($recentAppointments->isEmpty())
                    <div class="p-12 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <p class="text-base font-medium text-gray-700">No scheduled appointments found</p>
                        <p class="text-sm text-gray-400 mt-1">Patients will appear here once booked.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-gray-600 font-semibold text-xs uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-3.5 text-left">Serial #</th>
                                    <th class="px-6 py-3.5 text-left">Patient</th>
                                    <th class="px-6 py-3.5 text-left">Appointment Date</th>
                                    <th class="px-6 py-3.5 text-left">Status</th>
                                    <th class="px-6 py-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-800">
                                @foreach($recentAppointments as $appointment)
                                    <tr class="hover:bg-gray-50/70 transition">
                                        <td class="px-6 py-4 font-mono font-bold text-gray-700">
                                            #{{ str_pad($appointment->serial_number, 3, '0', STR_PAD_LEFT) }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-gray-900">{{ $appointment->patient?->name }}</div>
                                            <div class="text-xs text-gray-500">
                                                Blood: {{ $appointment->patient?->patientProfile?->blood_group ?? 'N/A' }} |
                                                Contact: {{ $appointment->patient?->patientProfile?->emergency_contact ?? $appointment->patient?->email }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            {{ $appointment->appointment_date ? $appointment->appointment_date->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($appointment->status === 'completed')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                    Completed
                                                </span>
                                            @elseif($appointment->status === 'confirmed')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                                    Confirmed
                                                </span>
                                            @elseif($appointment->status === 'cancelled')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                                    Cancelled
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            <a href="{{ route('doctor.patients.history', $appointment->patient_id) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg text-xs font-medium transition">
                                                Consent History
                                            </a>
                                            @if($appointment->status !== 'completed' && $appointment->status !== 'cancelled')
                                                <a href="{{ route('doctor.prescriptions.create', $appointment) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white hover:bg-indigo-700 rounded-lg text-xs font-medium shadow-sm transition">
                                                    Write Prescription
                                                </a>
                                            @else
                                                <span class="text-xs text-gray-400 italic">Rx Prescribed</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
