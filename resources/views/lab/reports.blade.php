<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Pending reports</h2>
            <p class="text-sm text-slate-500">Enter a report reference or a patient reference. Names and records are not shown.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <form method="GET" action="{{ route('lab.reports.index') }}" class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-col sm:flex-row gap-3">
                <input name="reference" value="{{ $reference }}" placeholder="LAB-2026-00125 or PAT-00125" class="flex-1 rounded-lg border-slate-300 text-sm">
                <button type="submit" class="px-4 py-2 rounded-lg bg-teal-700 text-white text-sm font-semibold">Find report</button>
            </form>

            @if($notice)
                <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-900">{{ $notice }}</div>
            @endif

            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Report ID</th>
                            <th class="px-5 py-3">Patient reference</th>
                            <th class="px-5 py-3">Test</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reference === '' ? $reports : $matches as $report)
                            <tr>
                                <td class="px-5 py-3 font-mono">{{ $report->reference }}</td>
                                <td class="px-5 py-3 font-mono">{{ $report->patientReference() }}</td>
                                <td class="px-5 py-3">{{ $report->report_title }}</td>
                                <td class="px-5 py-3">Pending upload</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('lab.reports.upload', $report) }}" class="text-teal-700 font-semibold">Upload</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-slate-400">
                                    {{ $reference === '' ? 'No reports are waiting for upload.' : 'Nothing to upload for that reference.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
