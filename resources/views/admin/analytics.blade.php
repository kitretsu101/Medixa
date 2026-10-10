<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">OPD analytics</h2>
            <p class="text-sm text-slate-500">Measured from recorded visits. Simulated comparisons are labeled separately.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 space-y-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach([
                    'Today' => $metrics['today_total'],
                    'Completed' => $metrics['today_completed'],
                    'Cancelled' => $metrics['today_cancelled'],
                    'No-show' => $metrics['today_no_show'],
                ] as $label => $value)
                    <div class="bg-white rounded-2xl border border-slate-200 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</p>
                        <p class="text-3xl font-bold text-slate-900">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-5 grid md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="font-semibold text-slate-900">Measured waiting time</p>
                    @if($metrics['average_wait'] === null)
                        <p class="text-slate-500 mt-1">Not enough completed check-ins yet. Estimates use a {{ \App\Services\QueueOptimizationService::BASELINE_MINUTES }}-minute baseline until five consultations are recorded.</p>
                    @else
                        <p class="mt-1">Average {{ $metrics['average_wait'] }} min · median {{ $metrics['median_wait'] }} min · longest {{ $metrics['max_wait'] }} min</p>
                        <p class="text-slate-500">From {{ $metrics['measured_visits'] }} recorded check-in to consultation intervals.</p>
                    @endif
                </div>
                <div>
                    <p class="font-semibold text-slate-900">Operations</p>
                    <p class="mt-1">Waiting now: {{ $metrics['today_waiting'] }}</p>
                    <p>Peak check-in hour (30 days): {{ $metrics['peak_hour'] ?? 'Not enough check-ins yet' }}</p>
                    <p>Past no-show rate: {{ $metrics['no_show_rate'] === null ? 'Not enough past appointments' : $metrics['no_show_rate'].'%' }}</p>
                    <p>Patients: {{ $metrics['patients']['total'] }} · new in 30 days: {{ $metrics['patients']['new'] }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 font-semibold">Department load today</div>
                <table class="min-w-full text-sm">
                    <tbody>
                        @forelse($metrics['departments'] as $department)
                            <tr class="border-t border-slate-100">
                                <td class="px-5 py-3">{{ $department['name'] }}</td>
                                <td class="px-5 py-3 text-right font-mono">{{ $department['appointments'] }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-5 py-6 text-slate-400">No departments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 font-semibold">Doctor workload</div>
                <p class="px-5 text-xs text-slate-500">Operational load only. This is not a performance score.</p>
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-2">Doctor</th>
                            <th class="px-5 py-2">Today</th>
                            <th class="px-5 py-2">Completed</th>
                            <th class="px-5 py-2">Pending reports</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($metrics['doctors'] as $doctor)
                            <tr class="border-t border-slate-100">
                                <td class="px-5 py-3">{{ $doctor['name'] }}<div class="text-xs text-slate-400">{{ $doctor['department'] }}</div></td>
                                <td class="px-5 py-3">{{ $doctor['today'] }}</td>
                                <td class="px-5 py-3">{{ $doctor['completed'] }}</td>
                                <td class="px-5 py-3">{{ $doctor['pending_reports'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-6 text-slate-400">No doctors yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <h3 class="font-semibold">Feedback</h3>
                @if($metrics['feedback']['responses'] === 0)
                    <p class="text-sm text-slate-500 mt-1">No feedback has been submitted.</p>
                @else
                    <p class="text-sm mt-1">{{ $metrics['feedback']['responses'] }} responses. Waiting {{ $metrics['feedback']['waiting'] }} · Doctor {{ $metrics['feedback']['doctor'] }} · System {{ $metrics['feedback']['system'] }} (1–5).</p>
                @endif
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <h3 class="font-semibold">Queue algorithm comparison</h3>
                <p class="text-sm text-slate-500 mt-1">{{ $metrics['comparison']['label'] }}</p>
                <p class="text-xs text-slate-400 mt-1">Sample size {{ $metrics['comparison']['sample_size'] }}. Consultation length {{ $metrics['comparison']['consultation_minutes'] }} minutes.</p>
                <table class="min-w-full text-sm mt-4">
                    <thead class="text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="py-2">Algorithm</th>
                            <th class="py-2">Avg wait</th>
                            <th class="py-2">Median</th>
                            <th class="py-2">Max</th>
                            <th class="py-2">Emergency</th>
                            <th class="py-2">Senior</th>
                            <th class="py-2">Normal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($metrics['comparison']['algorithms'] as $row)
                            <tr class="border-t border-slate-100">
                                <td class="py-2 font-medium">{{ $row['name'] }}</td>
                                <td class="py-2">{{ $row['average_wait'] }} min</td>
                                <td class="py-2">{{ $row['median_wait'] }} min</td>
                                <td class="py-2">{{ $row['max_wait'] }} min</td>
                                <td class="py-2">{{ $row['emergency_wait'] }} min</td>
                                <td class="py-2">{{ $row['senior_wait'] }} min</td>
                                <td class="py-2">{{ $row['normal_wait'] }} min</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
