<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
                    {{ __('Consent Center & Audit Logs') }}
                </h2>
                <p class="text-sm text-slate-500">Full visibility and sovereign control over who can access your medical data, with real-time revocation.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Consent-Scoped Access Active
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

            @if(session('status'))
                <div class="rounded-xl bg-teal-50 border border-teal-200 p-4 flex items-center gap-3 text-teal-800 text-sm shadow-sm">
                    <svg class="w-5 h-5 text-teal-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <!-- Section 1: Active & Historical Consent Tokens -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 sm:px-8 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Issued Consent Tokens</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Time-bound cryptographic authorization tokens issued to healthcare professionals.</p>
                    </div>
                    <span class="text-xs font-semibold px-3 py-1 bg-slate-100 text-slate-700 rounded-full">
                        {{ $tokens->count() }} Total Tokens
                    </span>
                </div>

                @if($tokens->isEmpty())
                    <div class="p-12 text-center text-sm text-slate-400">
                        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        No consent tokens have been generated yet. When you upload a diagnostic report, a 24-hour token is automatically created.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/75 border-b border-slate-100 text-[11px] uppercase tracking-wider font-semibold text-slate-500">
                                    <th class="py-3.5 px-6">Grantee / Doctor</th>
                                    <th class="py-3.5 px-6">Resource</th>
                                    <th class="py-3.5 px-6">Issued At</th>
                                    <th class="py-3.5 px-6">Expires At</th>
                                    <th class="py-3.5 px-6">Status</th>
                                    <th class="py-3.5 px-6 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                @foreach($tokens as $token)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="py-4 px-6">
                                            <div class="font-semibold text-slate-900"><x-doctor-name :name="$token->grantee?->name ?? 'Unknown'" /></div>
                                            <div class="text-xs text-slate-400 font-mono">{{ $token->grantee?->email ?? '' }}</div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-700">
                                                <span class="capitalize">{{ str_replace('_', ' ', $token->resource_type) }}</span>
                                                <span class="text-slate-400">#{{ $token->resource_id }}</span>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-xs text-slate-600">
                                            {{ $token->issued_at ? $token->issued_at->format('M d, Y H:i') : 'N/A' }}
                                        </td>
                                        <td class="py-4 px-6 text-xs text-slate-600">
                                            @if($token->expires_at)
                                                <span class="{{ $token->isExpired() ? 'text-rose-600 font-medium' : '' }}">
                                                    {{ $token->expires_at->format('M d, Y H:i') }}
                                                </span>
                                            @else
                                                <span class="text-slate-400">Never</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6">
                                            @if($token->isRevoked())
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                                    Revoked
                                                </span>
                                            @elseif($token->isExpired())
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                                                    Expired
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                    Active
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            @if($token->isActive())
                                                <form method="POST" action="{{ route('patient.consent.revoke', $token->id) }}" onsubmit="return confirm('Revoke this doctor\'s access immediately?');">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold rounded-lg transition border border-rose-200">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                        Revoke Access
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs text-slate-400">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Section 2: Real-time Immutable Audit Log Viewer -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 sm:px-8 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-900 text-white">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-400"></span>
                            <h3 class="text-lg font-bold text-white">Access Audit Log</h3>
                        </div>
                        <p class="text-xs text-slate-300 mt-0.5">Immutable audit trail of every medical record access event for your profile.</p>
                    </div>
                    <span class="text-xs font-semibold px-3 py-1 bg-white/10 text-teal-300 rounded-full border border-teal-500/20">
                        {{ $auditLogs->count() }} Audit Records
                    </span>
                </div>

                @if($auditLogs->isEmpty())
                    <div class="p-12 text-center text-sm text-slate-400">
                        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        No audit events recorded yet. Every time a doctor or staff member accesses your reports, a secure audit entry is written.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/75 border-b border-slate-100 text-[11px] uppercase tracking-wider font-semibold text-slate-500">
                                    <th class="py-3.5 px-6">Timestamp</th>
                                    <th class="py-3.5 px-6">Actor (Who Accessed)</th>
                                    <th class="py-3.5 px-6">Resource Accessed</th>
                                    <th class="py-3.5 px-6">Consent Token ID</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm font-sans">
                                @foreach($auditLogs as $log)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="py-4 px-6 text-xs text-slate-600 font-mono">
                                            {{ $log->accessed_at ? $log->accessed_at->format('Y-m-d H:i:s') : 'N/A' }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="font-semibold text-slate-900">{{ $log->actor?->name ?? 'System' }}</div>
                                            <div class="text-xs text-slate-400 capitalize">{{ $log->actor?->role ?? 'User' }}</div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-700">
                                                <span class="capitalize">{{ str_replace('_', ' ', $log->resource_type) }}</span>
                                                <span class="text-slate-400">#{{ $log->resource_id }}</span>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6">
                                            @if($log->consent_token_id)
                                                <span class="font-mono text-xs text-slate-500 bg-slate-100 px-2 py-1 rounded border border-slate-200">
                                                    {{ substr($log->consent_token_id, 0, 13) }}...
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-400 italic">Direct Owner Access</span>
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
