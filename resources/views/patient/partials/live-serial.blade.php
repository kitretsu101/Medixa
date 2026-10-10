@if($activeAppointment && $activeAppointment->doctor)
    @php
        $doctorQueue = $activeAppointment->doctor->liveQueue;
        $isDoctorPresent = (bool) ($doctorQueue?->doctor_present_status ?? false);
        $currentSerial = (int) ($doctorQueue?->current_running_serial ?? 0);
        $patientSerial = (int) $activeAppointment->serial_number;
    @endphp
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 md:p-8 text-white shadow-xl border border-indigo-900/50 relative overflow-hidden">
        <div class="absolute -right-12 -top-12 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 relative z-10">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        <span class="w-2 h-2 mr-2 bg-emerald-400 rounded-full animate-ping"></span> Live chamber queue
                    </span>
                    <span id="queue-presence-badge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $isDoctorPresent ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $isDoctorPresent ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                        <span>{{ $isDoctorPresent ? 'In Chamber' : 'Doctor Away' }}</span>
                    </span>
                </div>

                <h3 class="text-xl md:text-2xl font-black tracking-tight text-white">
                    Doctor's ongoing serial
                </h3>
                <p class="text-sm text-slate-300">
                    <x-doctor-name :name="$activeAppointment->doctor->name" />
                    <span class="text-slate-400">({{ $activeAppointment->doctor->doctorProfile?->department?->name ?? 'OPD' }})</span>
                    · {{ $activeAppointment->appointment_date?->format('M d, Y') }}
                </p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 min-w-[280px]">
                <div class="bg-white/5 border border-white/10 rounded-2xl p-4 text-center">
                    <span class="text-[10px] uppercase tracking-widest text-slate-400 font-semibold block">Now serving</span>
                    <span id="queue-current-serial" class="block font-mono text-4xl font-black text-emerald-400 mt-1">#{{ $currentSerial }}</span>
                    <span id="queue-doctor-status" class="block text-xs text-slate-400 mt-1">Doctor {{ $isDoctorPresent ? 'Present' : 'Away' }}</span>
                </div>
                <div class="bg-white/5 border border-white/10 rounded-2xl p-4 text-center">
                    <span class="text-[10px] uppercase tracking-widest text-slate-400 font-semibold block">Your serial</span>
                    <span id="queue-patient-serial" class="block font-mono text-4xl font-black text-amber-300 mt-1">#{{ $patientSerial }}</span>
                    <span id="queue-turn-message" class="block text-xs text-slate-300 mt-1">
                        @if($currentSerial === 0)
                            Session starting shortly
                        @elseif($currentSerial === $patientSerial)
                            Your turn now
                        @elseif($currentSerial < $patientSerial)
                            {{ $patientSerial - $currentSerial }} ahead of you
                        @else
                            Your serial was called
                        @endif
                    </span>
                </div>
                <div class="bg-white/5 border border-white/10 rounded-2xl p-4 text-center">
                    <span class="text-[10px] uppercase tracking-widest text-slate-400 font-semibold block">Patients ahead</span>
                    <span id="queue-patients-ahead" class="block font-mono text-4xl font-black text-white mt-1">{{ $queueEstimate['patients_ahead'] ?? '—' }}</span>
                    <span class="block text-xs text-slate-400 mt-1">Estimate</span>
                </div>
                <div class="bg-white/5 border border-white/10 rounded-2xl p-4 text-center">
                    <span class="text-[10px] uppercase tracking-widest text-slate-400 font-semibold block">Est. wait</span>
                    <span id="queue-wait-minutes" class="block font-mono text-4xl font-black text-white mt-1">{{ $queueEstimate['estimated_wait_minutes'] ?? '—' }}</span>
                    <span class="block text-xs text-slate-400 mt-1">minutes</span>
                </div>
            </div>
        </div>
        <div class="mt-4 text-sm text-slate-300 space-y-1 relative z-10">
            <p id="queue-explanation">{{ $queueEstimate['explanation'] ?? '' }}</p>
            <p id="queue-delay-message">{{ $queueEstimate['delay_message'] ?? '' }}</p>
            @if(!empty($queueEstimate['recommended_arrival']))
                <p id="queue-arrival">Recommended arrival about {{ $queueEstimate['recommended_arrival'] }}. This is an estimate, not a guaranteed time.</p>
            @else
                <p id="queue-arrival"></p>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const doctorId = {{ $activeAppointment->doctor_id }};
                const patientSerial = {{ (int) $activeAppointment->serial_number }};

                function applyQueue(currentSerial, isPresent, extra = {}) {
                    const doctorStatusEl = document.getElementById('queue-doctor-status');
                    const currentSerialEl = document.getElementById('queue-current-serial');
                    const patientSerialEl = document.getElementById('queue-patient-serial');
                    const aheadEl = document.getElementById('queue-patients-ahead');
                    const waitEl = document.getElementById('queue-wait-minutes');
                    const delayEl = document.getElementById('queue-delay-message');
                    const explainEl = document.getElementById('queue-explanation');

                    if (doctorStatusEl) {
                        doctorStatusEl.textContent = isPresent ? 'Doctor Present' : 'Doctor Away';
                    }
                    if (currentSerialEl) {
                        currentSerialEl.textContent = '#' + currentSerial;
                    }
                    if (patientSerialEl) {
                        patientSerialEl.textContent = '#' + patientSerial;
                    }
                    if (aheadEl && extra.patients_ahead !== undefined) {
                        aheadEl.textContent = extra.patients_ahead;
                    }
                    if (waitEl && extra.estimated_wait_minutes !== undefined) {
                        waitEl.textContent = extra.estimated_wait_minutes;
                    }
                    if (delayEl) {
                        delayEl.textContent = extra.delay_message || '';
                    }
                    if (explainEl && extra.explanation) {
                        explainEl.textContent = extra.explanation;
                    }

                    const presenceBadge = document.getElementById('queue-presence-badge');
                    if (presenceBadge) {
                        if (isPresent) {
                            presenceBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
                            presenceBadge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span><span>In Chamber</span>';
                        } else {
                            presenceBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20';
                            presenceBadge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span><span>Doctor Away</span>';
                        }
                    }

                    const turnMessageEl = document.getElementById('queue-turn-message');
                    if (turnMessageEl) {
                        if (currentSerial === 0) {
                            turnMessageEl.textContent = 'Session starting shortly';
                        } else if (currentSerial === patientSerial) {
                            turnMessageEl.textContent = 'Your turn now';
                        } else if (currentSerial < patientSerial) {
                            turnMessageEl.textContent = (patientSerial - currentSerial) + ' ahead of you';
                        } else {
                            turnMessageEl.textContent = 'Your serial was called';
                        }
                    }
                }

                async function pollQueue() {
                    try {
                        const response = await fetch(@json(route('patient.queue.status')), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        if (!response.ok) {
                            return;
                        }
                        const data = await response.json();
                        if (!data.active) {
                            return;
                        }
                        applyQueue(Number(data.current_serial ?? 0), Boolean(data.doctor_present), data);
                    } catch (error) {
                    }
                }

                function setupEchoListener() {
                    if (!window.Echo) {
                        setTimeout(setupEchoListener, 150);
                        return;
                    }

                    window.Echo.channel('queue.doctor.' + doctorId)
                        .listen('.queue.updated', (event) => {
                            applyQueue(Number(event.current_serial ?? 0), Boolean(event.doctor_present));
                        });
                }

                setupEchoListener();
                setInterval(pollQueue, 8000);
            });
        </script>
    @endpush
@endif
