<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Lab Assistant Portal</h2>
            <p class="text-sm text-slate-500">Upload laboratory reports. Patient records are not available here.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('status'))
                <div class="rounded-xl bg-teal-50 border border-teal-200 p-4 text-sm text-teal-800">{{ session('status') }}</div>
            @endif

            <form method="GET" action="{{ route('lab.reports.index') }}" class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-col sm:flex-row gap-3">
                <label for="reference" class="sr-only">Report or patient reference</label>
                <input id="reference" name="reference" value="{{ old('reference') }}" placeholder="LAB-2026-00125 or PAT-00125" class="flex-1 rounded-lg border-slate-300 text-sm">
                <button type="submit" class="px-4 py-2 rounded-lg bg-teal-700 text-white text-sm font-semibold">Find report</button>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Pending reports</p>
                    <p class="text-3xl font-bold text-slate-900 mt-1">{{ $pendingCount }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Uploaded today</p>
                    <p class="text-3xl font-bold text-slate-900 mt-1">{{ $uploadedToday }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Uploaded</p>
                    <p class="text-3xl font-bold text-slate-900 mt-1">{{ $uploadedCount }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 font-semibold text-slate-900">Pending lab reports</div>
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Report ID</th>
                            <th class="px-5 py-3">Test</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reports as $report)
                            <tr>
                                <td class="px-5 py-3 font-mono">{{ $report->reference }}</td>
                                <td class="px-5 py-3">{{ $report->report_title }}</td>
                                <td class="px-5 py-3">Pending upload</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('lab.reports.upload', $report) }}" class="text-teal-700 font-semibold">Upload</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-slate-400">No reports are waiting for upload.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
