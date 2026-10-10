<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Upload history</h2>
            <p class="text-sm text-slate-500">Reports you have uploaded. Patient records are not included.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('status'))
                <div class="mb-4 rounded-xl bg-teal-50 border border-teal-200 p-4 text-sm text-teal-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Report ID</th>
                            <th class="px-5 py-3">Test</th>
                            <th class="px-5 py-3">Uploaded at</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reports as $report)
                            <tr>
                                <td class="px-5 py-3 font-mono">{{ $report->reference }}</td>
                                <td class="px-5 py-3">{{ $report->report_title }}</td>
                                <td class="px-5 py-3">{{ $report->uploaded_at?->format('M d, Y h:i A') }}</td>
                                <td class="px-5 py-3">Uploaded</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-slate-400">You have not uploaded a report yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
