<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
                    {{ __('Patient Portal') }}
                </h2>
                <p class="text-sm text-slate-500">Welcome back, {{ Auth::user()->name }}. Here is an overview of your medical records and active consents.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('patient.lab-reports.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Upload Report
                </a>
                <a href="{{ route('patient.assistance-requests.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    Get Help
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            @if(session('status'))
                <div class="rounded-xl bg-teal-50 border border-teal-200 p-4 flex items-center gap-3 text-teal-800 text-sm shadow-sm animate-fade-in">
                    <svg class="w-5 h-5 text-teal-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @include('patient.partials.live-serial')

            <!-- Quick Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Prescriptions Card -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Prescriptions</p>
                            <h3 class="text-3xl font-extrabold text-slate-900 mt-1">{{ $prescriptionCount }}</h3>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-50 flex items-center justify-between text-xs">
                        <a href="{{ route('patient.timeline') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">View Timeline &rarr;</a>
                    </div>
                </div>

                <!-- Lab Reports Card -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Lab Reports</p>
                            <h3 class="text-3xl font-extrabold text-slate-900 mt-1">{{ $labReportCount }}</h3>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-50 flex items-center justify-between text-xs">
                        <a href="{{ route('patient.lab-reports.create') }}" class="text-teal-600 hover:text-teal-800 font-medium">Upload New &rarr;</a>
                    </div>
                </div>

                <!-- Active Consents Card -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Consents</p>
                            <h3 class="text-3xl font-extrabold text-emerald-600 mt-1">{{ $activeConsentsCount }}</h3>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-50 flex items-center justify-between text-xs">
                        <a href="{{ route('patient.consent-center') }}" class="text-emerald-600 hover:text-emerald-800 font-medium">Consent Center &rarr;</a>
                    </div>
                </div>

                <!-- Assistance Requests Card -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Open Requests</p>
                            <h3 class="text-3xl font-extrabold text-amber-600 mt-1">{{ $openAssistanceCount }}</h3>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-50 flex items-center justify-between text-xs">
                        <a href="{{ route('patient.assistance-requests.create') }}" class="text-amber-600 hover:text-amber-800 font-medium">Request Help &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Quick Access Navigation Banners -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Medical Timeline Link -->
                <a href="{{ route('patient.timeline') }}" class="group block p-6 bg-gradient-to-br from-indigo-900 to-indigo-700 rounded-2xl text-white shadow-md hover:shadow-xl transition transform hover:-translate-y-0.5">
                    <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h4 class="text-lg font-bold mb-1">Unified Health Timeline</h4>
                    <p class="text-xs text-indigo-200">View chronological history of your prescriptions and medical diagnostics.</p>
                </a>

                <!-- Consent Center Link -->
                <a href="{{ route('patient.consent-center') }}" class="group block p-6 bg-gradient-to-br from-teal-900 to-teal-700 rounded-2xl text-white shadow-md hover:shadow-xl transition transform hover:-translate-y-0.5">
                    <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h4 class="text-lg font-bold mb-1">Consent Center & Audit</h4>
                    <p class="text-xs text-teal-200">Manage time-bound doctor access tokens and review immutable audit access logs.</p>
                </a>

                <!-- Health Profile Link -->
                <a href="{{ route('patient.profile') }}" class="group block p-6 bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl text-white shadow-md hover:shadow-xl transition transform hover:-translate-y-0.5">
                    <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <h4 class="text-lg font-bold mb-1">Medical Profile</h4>
                    <p class="text-xs text-slate-300">Keep your date of birth, blood group, and emergency contact details up to date.</p>
                </a>
            </div>

            <!-- Two Column Overview: Recent Prescriptions & Reports -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Prescriptions -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            <h4 class="font-bold text-slate-900">Recent Prescriptions</h4>
                        </div>
                        <a href="{{ route('patient.timeline') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">View All &rarr;</a>
                    </div>

                    <div class="mt-4 divide-y divide-slate-100">
                        @forelse($recentPrescriptions as $prescription)
                            <div class="py-3.5 flex items-start justify-between gap-4">
                                <div>
                                    <h5 class="text-sm font-semibold text-slate-900">{{ $prescription->diagnosis }}</h5>
                                    <p class="text-xs text-slate-500 mt-0.5">Prescribed by <x-doctor-name :name="$prescription->doctor?->name ?? 'Unknown'" /></p>
                                </div>
                                <span class="text-xs font-medium text-slate-400 shrink-0">
                                    {{ $prescription->created_at->format('M d, Y') }}
                                </span>
                            </div>
                        @empty
                            <div class="py-8 text-center text-sm text-slate-400">
                                No prescriptions on file yet.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Lab Reports -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-600"></span>
                            <h4 class="font-bold text-slate-900">Recent Diagnostic Reports</h4>
                        </div>
                        <a href="{{ route('patient.timeline') }}" class="text-xs font-semibold text-teal-600 hover:text-teal-800">View All &rarr;</a>
                    </div>

                    <div class="mt-4 divide-y divide-slate-100">
                        @forelse($recentLabReports as $report)
                            <div class="py-3.5 flex items-center justify-between gap-4">
                                <div>
                                    <h5 class="text-sm font-semibold text-slate-900">{{ $report->report_title }}</h5>
                                    <p class="text-xs text-slate-500 mt-0.5">Assigned to <x-doctor-name :name="$report->doctor?->name ?? 'None'" /></p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $report->review_status === 'reviewed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ ucfirst($report->review_status) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center text-sm text-slate-400">
                                No lab reports uploaded yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            </div>
        </div>
    </div>
</x-app-layout>
