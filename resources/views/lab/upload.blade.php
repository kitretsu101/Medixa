<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Upload laboratory report</h2>
            <p class="text-sm text-slate-500">Confirm the reference, then attach the file.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto px-4 sm:px-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">Report ID</p>
                    <p class="font-mono text-lg font-semibold text-slate-900">{{ $report->reference }}</p>
                </div>

                @if($alreadyUploaded)
                    <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-900">
                        This report has already been uploaded.
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 text-sm">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-500">Patient reference</p>
                            <p class="font-mono font-semibold text-slate-900">{{ $report->patientReference() }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-500">Test</p>
                            <p class="font-semibold text-slate-900">{{ $report->report_title }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-500">Status</p>
                            <p class="font-semibold text-slate-900">Pending upload</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-500">Date</p>
                            <p class="text-slate-900">{{ $report->created_at?->format('M d, Y') }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('lab.reports.store', $report) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label for="report_file" class="block text-sm font-semibold text-slate-700">Select report</label>
                            <input id="report_file" name="report_file" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="mt-2 block w-full text-sm">
                            <p class="mt-1 text-xs text-slate-500">PDF, JPG, or PNG. Maximum 10 MB.</p>
                            @error('report_file')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-lg bg-teal-700 text-white text-sm font-semibold">Upload report</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
