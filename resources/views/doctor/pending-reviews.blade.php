<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    Pending Lab Report Reviews
                </h2>
                <p class="text-sm text-gray-500 mt-1">Diagnostic reports submitted for your clinical review with auto-issued consent tokens</p>
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
                        <h3 class="text-lg font-bold text-gray-900">Review Inbox</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Tokens auto-expire to protect patient confidentiality. Review reports before the timer lapses.</p>
                    </div>
                    <span class="text-xs bg-amber-50 text-amber-700 font-semibold px-3 py-1 rounded-full border border-amber-200">
                        {{ $reports->total() }} Pending Reviews
                    </span>
                </div>

                @if($reports->isEmpty())
                    <div class="p-12 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-emerald-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-base font-medium text-gray-700">Inbox all clear!</p>
                        <p class="text-sm text-gray-400 mt-1">No pending diagnostic reports require your review right now.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-gray-600 font-semibold text-xs uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-3.5 text-left">Report Title</th>
                                    <th class="px-6 py-3.5 text-left">Patient</th>
                                    <th class="px-6 py-3.5 text-left">Uploaded At</th>
                                    <th class="px-6 py-3.5 text-left">Consent Expiry Countdown</th>
                                    <th class="px-6 py-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-800">
                                @foreach($reports as $report)
                                    <tr class="hover:bg-gray-50/70 transition">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-900">{{ $report->report_title }}</div>
                                            <div class="text-xs font-mono text-gray-400">{{ basename($report->file_path) }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900">{{ $report->patient?->name }}</div>
                                            <div class="text-xs text-gray-500">
                                                Blood: {{ $report->patient?->patientProfile?->blood_group ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600 text-xs">
                                            {{ $report->uploaded_at ? $report->uploaded_at->format('M d, Y H:i') : $report->created_at->format('M d, Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($report->active_consent_token && $report->active_consent_token->expires_at)
                                                <div x-data="countdown('{{ $report->active_consent_token->expires_at->toIso8601String() }}')" class="font-mono">
                                                    <span :class="isUrgent ? 'bg-red-100 text-red-800 border-red-300' : 'bg-amber-100 text-amber-800 border-amber-300'"
                                                          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border">
                                                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span x-text="timeLeft">Calculating...</span>
                                                    </span>
                                                    <div class="text-[10px] text-gray-400 mt-1">
                                                        Expires: {{ $report->active_consent_token->expires_at->format('M d, H:i') }}
                                                    </div>
                                                </div>
                                            @elseif($report->active_consent_token)
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                    Permanent (No Expiry)
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                                    Token Expired / Revoked
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            @if($report->active_consent_token && $report->active_consent_token->isActive())
                                                <a href="{{ route('doctor.lab-reports.show', $report) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white hover:bg-indigo-700 rounded-lg text-xs font-semibold shadow-sm transition">
                                                    View Report &rarr;
                                                </a>
                                            @else
                                                <span class="text-xs text-gray-400 italic">Access Inactive</span>
                                            @endif
                                            <a href="{{ route('doctor.patients.history', $report->patient_id) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg text-xs font-medium transition">
                                                History
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($reports->hasPages())
                        <div class="p-4 border-t border-gray-100">
                            {{ $reports->links() }}
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>

    <script>
        function countdown(expiryIsoString) {
            return {
                expiry: new Date(expiryIsoString).getTime(),
                timeLeft: '',
                isUrgent: false,
                timer: null,
                init() {
                    this.update();
                    this.timer = setInterval(() => this.update(), 1000);
                },
                update() {
                    const now = new Date().getTime();
                    const distance = this.expiry - now;

                    if (distance <= 0) {
                        this.timeLeft = 'Expired';
                        this.isUrgent = true;
                        clearInterval(this.timer);
                        return;
                    }

                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    this.isUrgent = distance < (1000 * 60 * 30); // under 30 mins
                    this.timeLeft = `${hours}h ${minutes}m ${seconds}s`;
                }
            }
        }
    </script>
</x-app-layout>
