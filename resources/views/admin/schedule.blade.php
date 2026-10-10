<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-900 leading-tight">
                    {{ __('Doctor Schedule & Live Queue Oversight') }}
                </h2>
                <p class="text-sm text-slate-500 mt-1">Real-time monitor of OPD consultations, daily throughput, and chamber queues</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-2 h-2 mr-1.5 bg-emerald-500 rounded-full animate-ping"></span> Live Hospital OPD
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Summary Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Doctors On Duty</p>
                    <h3 class="text-3xl font-extrabold text-slate-900 mt-1">
                        {{ $doctors->filter(fn($d) => $d->liveQueue?->doctor_present_status)->count() }} / {{ $doctors->count() }}
                    </h3>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Consultations Scheduled Today</p>
                    <h3 class="text-3xl font-extrabold text-indigo-600 mt-1">
                        {{ $todayAppointments->sum('total') }}
                    </h3>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Completed Consultations Today</p>
                    <h3 class="text-3xl font-extrabold text-teal-600 mt-1">
                        {{ $todayAppointments->sum('completed') }}
                    </h3>
                </div>
            </div>

            <!-- Doctor Queue & Schedule Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($doctors as $doctor)
                    @php
                        $appt = $todayAppointments->get($doctor->id);
                        $total = $appt->total ?? 0;
                        $completed = $appt->completed ?? 0;
                        $remaining = max(0, $total - $completed);
                        $isPresent = (bool) ($doctor->liveQueue?->doctor_present_status ?? false);
                        $currentToken = (int) ($doctor->liveQueue?->current_running_serial ?? 0);
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex flex-col justify-between hover:shadow-md transition">
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-lg shrink-0">
                                        {{ strtoupper(substr($doctor->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-900 text-base leading-tight">{{ $doctor->name }}</h4>
                                        <span class="inline-block mt-1 px-2.5 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-700">
                                            {{ $doctor->doctorProfile?->department?->name ?? 'General' }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    @if($isPresent)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 mr-1.5 bg-emerald-500 rounded-full animate-pulse"></span> Present
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-500">
                                            Away
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <p class="text-xs text-slate-500 mb-4">
                                {{ $doctor->doctorProfile?->designation }} &bull; {{ $doctor->doctorProfile?->qualification }}
                            </p>

                            <!-- Queue Indicator -->
                            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 mb-4">
                                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                                    <span>Now Serving</span>
                                    <span>Total Scheduled</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-2xl font-black text-indigo-700">
                                        #{{ $currentToken }}
                                    </span>
                                    <span class="text-base font-bold text-slate-800">
                                        {{ $total }} Patients
                                    </span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-medium text-slate-600">
                                    <span>Completed: {{ $completed }}</span>
                                    <span>Pending: {{ $remaining }}</span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                    <div
                                        class="h-full bg-gradient-to-r from-teal-500 to-indigo-600 rounded-full transition-all duration-500"
                                        style="width: {{ $total > 0 ? min(100, round(($completed / $total) * 100)) : 0 }}%"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <span>Lifetime consultations:</span>
                            <span class="font-bold text-slate-800">{{ number_format($doctor->doctorProfile?->patients_seen_count ?? 0) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-slate-400 bg-white rounded-2xl border border-slate-100">
                        No doctors registered in the system.
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
