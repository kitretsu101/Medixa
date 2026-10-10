<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                        Clinical History: {{ $patient->name }}
                    </h2>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                        Consent-Scoped Access
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Only prescriptions and lab reports covered by an active consent token are listed below.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('doctor.patients.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg text-xs font-semibold uppercase tracking-wider transition">
                    &larr; Patient Directory
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('doctor.lab-orders.store', $patient) }}" class="bg-white border border-gray-200 rounded-xl p-4 flex flex-col sm:flex-row gap-3 sm:items-end">
                @csrf
                <div class="flex-1">
                    <label for="test_name" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Order a laboratory test</label>
                    <input id="test_name" name="test_name" value="{{ old('test_name') }}" placeholder="Complete Blood Count (CBC)" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    @error('test_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold">Create lab order</button>
            </form>

            <!-- Consent Notice Banner -->
            <div class="p-5 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl shadow-sm text-amber-900">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-amber-100 rounded-lg text-amber-700 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-sm tracking-wide uppercase text-amber-950">
                            Strict Consent-Scoped Clinical View
                        </h4>
                        <p class="text-xs text-amber-800 mt-1 leading-relaxed">
                            This patient record view is governed by Medixa's cryptographically verifiable, patient-delegated consent tokens. 
                            <strong>Only resources for which you hold an active, unexpired, non-revoked consent token are displayed below.</strong>
                            Any additional records existing in the hospital database are strictly shielded from view.
                            All access events have been recorded in the immutable audit ledger.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-white border border-amber-200 font-medium text-amber-900">
                                Active Tokens: <strong>{{ $activeTokens->count() }}</strong>
                            </span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-white border border-amber-200 font-medium text-amber-900">
                                Consented Lab Reports: <strong>{{ $labReports->count() }}</strong>
                            </span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-white border border-amber-200 font-medium text-amber-900">
                                Consented Prescriptions: <strong>{{ $prescriptions->count() }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Section 1: Consented Prescriptions -->
                <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="font-bold text-gray-900">Consented Prescriptions</h3>
                        </div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                            {{ $prescriptions->count() }} Available
                        </span>
                    </div>

                    <div class="p-6 flex-1 space-y-4">
                        @if($prescriptions->isEmpty())
                            <div class="p-8 text-center text-gray-400">
                                <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-600">No consented prescriptions</p>
                                <p class="text-xs text-gray-400 mt-1">No active consent token has been granted for prescription records.</p>
                            </div>
                        @else
                            @foreach($prescriptions as $prescription)
                                <div class="p-4 border border-gray-200 rounded-xl bg-gray-50/40 hover:bg-white hover:shadow-sm transition">
                                    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                                        <div class="text-xs font-bold text-gray-700">
                                            Rx Date: {{ $prescription->created_at->format('M d, Y') }}
                                        </div>
                                        <span class="text-xs text-gray-500 font-mono">
                                            Prescribed by: <x-doctor-name :name="$prescription->doctor?->name ?? 'OPD'" />
                                        </span>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Diagnosis</p>
                                        <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $prescription->diagnosis }}</p>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Medicines</p>
                                        <div class="mt-1 space-y-1">
                                            @foreach($prescription->medicines ?? [] as $medicine)
                                                <div class="text-xs bg-white p-2 rounded border border-gray-100 flex items-center justify-between">
                                                    <span class="font-bold text-gray-800">{{ $medicine['name'] ?? '' }} ({{ $medicine['dosage'] ?? '' }})</span>
                                                    <span class="text-gray-500 font-mono">{{ $medicine['frequency'] ?? '' }} &bull; {{ $medicine['duration'] ?? '' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @if($prescription->instructions)
                                        <div class="mt-2 text-xs text-gray-600 italic">
                                            Instructions: {{ $prescription->instructions }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <!-- Section 2: Consented Lab Reports -->
                <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            </div>
                            <h3 class="font-bold text-gray-900">Consented Diagnostic Reports</h3>
                        </div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
                            {{ $labReports->count() }} Available
                        </span>
                    </div>

                    <div class="p-6 flex-1 space-y-4">
                        @if($labReports->isEmpty())
                            <div class="p-8 text-center text-gray-400">
                                <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-600">No consented lab reports</p>
                                <p class="text-xs text-gray-400 mt-1">No active consent token has been granted for diagnostic reports.</p>
                            </div>
                        @else
                            @foreach($labReports as $report)
                                <div class="p-4 border border-gray-200 rounded-xl bg-gray-50/40 hover:bg-white hover:shadow-sm transition">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="font-bold text-sm text-gray-900">{{ $report->report_title }}</h4>
                                            <p class="text-xs text-gray-500 mt-0.5">Uploaded: {{ $report->uploaded_at ? $report->uploaded_at->format('M d, Y - H:i') : $report->created_at->format('M d, Y') }}</p>
                                        </div>
                                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $report->review_status === 'reviewed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ ucfirst($report->review_status) }}
                                        </span>
                                    </div>
                                    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">
                                        <span class="text-xs font-mono text-gray-500 truncate max-w-xs">
                                            Path: {{ basename($report->file_path) }}
                                        </span>
                                        <a href="{{ route('doctor.lab-reports.open', $report) }}" class="inline-flex items-center px-3 py-1 bg-indigo-600 text-white hover:bg-indigo-700 rounded-lg text-xs font-semibold shadow-sm transition">
                                            Inspect Document &rarr;
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <!-- Active Consent Tokens Breakdown -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                <h4 class="font-bold text-gray-900 text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Underlying Active Consent Delegations
                </h4>
                @if($activeTokens->isEmpty())
                    <p class="text-xs text-gray-400">Zero active tokens found for this patient.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-xs font-mono">
                            <thead class="text-gray-500 uppercase tracking-wider text-left bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2">Token UUID</th>
                                    <th class="px-4 py-2">Resource Type</th>
                                    <th class="px-4 py-2">Resource ID</th>
                                    <th class="px-4 py-2">Issued At</th>
                                    <th class="px-4 py-2">Expires At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                @foreach($activeTokens as $token)
                                    <tr>
                                        <td class="px-4 py-2 font-bold text-indigo-700">{{ $token->id }}</td>
                                        <td class="px-4 py-2 uppercase">{{ $token->resource_type }}</td>
                                        <td class="px-4 py-2 font-bold">#{{ $token->resource_id }}</td>
                                        <td class="px-4 py-2">{{ $token->issued_at ? $token->issued_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                        <td class="px-4 py-2 text-emerald-600">{{ $token->expires_at ? $token->expires_at->format('Y-m-d H:i') : 'Never (Permanent)' }}</td>
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
