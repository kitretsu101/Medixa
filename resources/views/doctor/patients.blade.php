<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    Linked Patient Directory
                </h2>
                <p class="text-sm text-gray-500 mt-1">Patients with scheduled or completed appointments linked to your care</p>
            </div>
            <a href="{{ route('doctor.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg text-xs font-semibold uppercase tracking-wider transition">
                &larr; Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Your Active Patients</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Click "Consent History" to inspect records granted under active patient consent tokens.</p>
                    </div>
                    <span class="text-xs bg-indigo-50 text-indigo-700 font-semibold px-3 py-1 rounded-full">
                        {{ $patients->total() }} Total Patients
                    </span>
                </div>

                @if($patients->isEmpty())
                    <div class="p-12 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <p class="text-base font-medium text-gray-700">No linked patients yet</p>
                        <p class="text-sm text-gray-400 mt-1">Patients will automatically appear here once an appointment is booked with you.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-gray-600 font-semibold text-xs uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-3.5 text-left">Patient Name</th>
                                    <th class="px-6 py-3.5 text-left">Contact & Blood Group</th>
                                    <th class="px-6 py-3.5 text-center">Appointments</th>
                                    <th class="px-6 py-3.5 text-left">Last Appointment</th>
                                    <th class="px-6 py-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-800">
                                @foreach($patients as $patient)
                                    <tr class="hover:bg-gray-50/70 transition">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-900">{{ $patient->name }}</div>
                                            <div class="text-xs text-gray-500 font-mono">{{ $patient->email }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-xs">
                                                <span class="font-semibold text-gray-700">Blood:</span> 
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-50 text-rose-700">
                                                    {{ $patient->patientProfile?->blood_group ?? 'Unspecified' }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                <span class="font-semibold">Emergency:</span> {{ $patient->patientProfile?->emergency_contact ?? 'None listed' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                                {{ $patient->doctor_appointments_count }} OPD Visits
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            @if($patient->latest_appointment)
                                                <div class="font-medium text-gray-900">
                                                    {{ $patient->latest_appointment->appointment_date ? $patient->latest_appointment->appointment_date->format('M d, Y') : 'N/A' }}
                                                </div>
                                                <div class="text-xs capitalize text-gray-500">
                                                    Status: {{ $patient->latest_appointment->status }}
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400">None</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            <a href="{{ route('doctor.patients.history', $patient) }}" class="inline-flex items-center px-3.5 py-1.5 bg-indigo-600 text-white hover:bg-indigo-700 rounded-lg text-xs font-semibold shadow-sm transition">
                                                Consent History &rarr;
                                            </a>
                                            @if($patient->latest_appointment && $patient->latest_appointment->status !== 'completed' && $patient->latest_appointment->status !== 'cancelled')
                                                <a href="{{ route('doctor.prescriptions.create', $patient->latest_appointment) }}" class="inline-flex items-center px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-xs font-semibold shadow-sm transition">
                                                    Prescribe
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($patients->hasPages())
                        <div class="p-4 border-t border-gray-100">
                            {{ $patients->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
