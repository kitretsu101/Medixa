<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-900 leading-tight">
                    {{ __('Audit Oversight & Compliance') }}
                </h2>
                <p class="text-sm text-slate-500 mt-1">Aggregated access counts and patterns across the hospital</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                    <svg class="w-3.5 h-3.5 text-emerald-600 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Zero-Knowledge Privacy Guaranteed
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Privacy Architecture Notice -->
            <div class="rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 text-white shadow-lg border border-slate-800">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-500/30">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Aggregate-Only Oversight Policy</h3>
                            <p class="text-xs text-slate-300 mt-1 max-w-3xl leading-relaxed">
                                Under Medixa's clinical compliance architecture, administrators oversee aggregate security patterns and volume anomalies (e.g. total queries per practitioner per week). Administrators are strictly restricted from inspecting individual medical contents, clinical diagnosis notes, or patient identifiers.
                            </p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-white/10 rounded-lg text-xs font-mono text-indigo-200 shrink-0 border border-white/10">
                        AUDIT_LOG_AGGREGATE
                    </span>
                </div>
            </div>

            <!-- Top Level Aggregate Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Audit Events</p>
                    <h3 class="text-3xl font-extrabold text-slate-900 mt-1">{{ number_format($totalStats['total_accesses']) }}</h3>
                    <p class="text-xs text-slate-400 mt-1">Lifetime recorded data accesses</p>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Accesses This Week</p>
                    <h3 class="text-3xl font-extrabold text-indigo-600 mt-1">{{ number_format($totalStats['this_week']) }}</h3>
                    <p class="text-xs text-slate-400 mt-1">Last 7 calendar days</p>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Actors</p>
                    <h3 class="text-3xl font-extrabold text-teal-600 mt-1">{{ number_format($totalStats['unique_actors']) }}</h3>
                    <p class="text-xs text-slate-400 mt-1">Clinical staff accessing data</p>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Unique Patients Accessed</p>
                    <h3 class="text-3xl font-extrabold text-amber-600 mt-1">{{ number_format($totalStats['unique_patients_accessed']) }}</h3>
                    <p class="text-xs text-slate-400 mt-1">Distinct patient records touched</p>
                </div>
            </div>

            <!-- Weekly Doctor Access Counts Table (Aggregate Only) -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h4 class="text-lg font-bold text-slate-900">Doctor Access Volume (Past 7 Days)</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Aggregate record query counts per practitioner — showing volume without exposing patient data</p>
                    </div>
                    <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                        {{ $weeklyAccessByDoctor->count() }} Active Practitioners
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-4 px-6">Doctor</th>
                                <th class="py-4 px-6 text-center">Total Records Accessed</th>
                                <th class="py-4 px-6 text-center">Distinct Patients</th>
                                <th class="py-4 px-6 text-center">Average Records / Patient</th>
                                <th class="py-4 px-6 text-right">Access Volume Pattern</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @forelse($weeklyAccessByDoctor as $item)
                                @php
                                    $avg = $item->unique_patients > 0 ? round($item->access_count / $item->unique_patients, 1) : 0;
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-4 px-6 font-bold text-slate-900">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-sm shrink-0">
                                                {{ strtoupper(substr($item->doctor_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-900">{{ $item->doctor_name }}</p>
                                                <p class="text-xs text-slate-400">Doctor ID #{{ $item->doctor_id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            {{ $item->access_count }} records
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-center font-semibold text-slate-700">
                                        {{ $item->unique_patients }} patients
                                    </td>
                                    <td class="py-4 px-6 text-center text-slate-600">
                                        {{ $avg }} / pt
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        @if($item->access_count > 30)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                High Throughput
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                                                Normal OPD
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400">
                                        No doctor access events recorded in the last 7 days.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Resource Type Breakdown -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                    <h4 class="text-base font-bold text-slate-900 mb-1">Resource Breakdown (This Week)</h4>
                    <p class="text-xs text-slate-500 mb-6">Distribution of accessed medical entities</p>

                    <div class="space-y-4">
                        @forelse($resourceBreakdown as $resource)
                            @php
                                $totalWeekly = $totalStats['this_week'] > 0 ? $totalStats['this_week'] : 1;
                                $percentage = round(($resource->count / $totalWeekly) * 100);
                            @endphp
                            <div>
                                <div class="flex justify-between text-xs font-semibold text-slate-700 mb-1.5">
                                    <span class="capitalize">{{ str_replace('_', ' ', $resource->resource_type) }}</span>
                                    <span>{{ $resource->count }} accesses ({{ $percentage }}%)</span>
                                </div>
                                <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div
                                        class="h-full bg-indigo-600 rounded-full"
                                        style="width: {{ $percentage }}%"
                                    ></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 py-6 text-center">No resource access data recorded this week.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Daily Trend (Last 30 Days) -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                    <h4 class="text-base font-bold text-slate-900 mb-1">Daily Query Trend (Past 30 Days)</h4>
                    <p class="text-xs text-slate-500 mb-6">System-wide clinical activity volume over time</p>

                    <div class="space-y-2 max-h-64 overflow-y-auto pr-2">
                        @forelse($dailyTrend as $trend)
                            <div class="flex items-center justify-between py-2 border-b border-slate-50 text-xs">
                                <span class="font-medium text-slate-600">{{ \Carbon\Carbon::parse($trend->date)->format('M d, Y') }}</span>
                                <span class="font-bold text-indigo-600 px-2.5 py-0.5 rounded-full bg-indigo-50">
                                    {{ $trend->total_accesses }} queries
                                </span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 py-6 text-center">No daily activity recorded in past 30 days.</p>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
