<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
                    {{ __('Upload Lab Report') }}
                </h2>
                <p class="text-sm text-slate-500">Securely store your medical diagnostics and grant time-limited doctor access.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Security & Automatic Consent Notice Banner -->
            <div class="bg-gradient-to-r from-teal-900 to-indigo-950 rounded-2xl p-6 text-white shadow-md border border-teal-800/40">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-teal-500/20 flex items-center justify-center text-teal-300 shrink-0 border border-teal-400/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-base text-teal-100 flex items-center gap-2">
                            <span>Automatic 24-Hour Consent Token</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-teal-400/20 text-teal-200 border border-teal-400/30">Privacy First</span>
                        </h4>
                        <p class="text-xs text-slate-300 mt-1.5 leading-relaxed">
                            Files are saved to an isolated, encrypted private disk. Upon upload, our system automatically generates a <strong class="text-white font-semibold">24-hour consent token</strong> granting access to your assigned physician. The token will expire automatically or can be revoked anytime in your Consent Center.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Upload Form -->
            <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
                <form method="POST" action="{{ route('patient.lab-reports.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <!-- Report Title -->
                    <div>
                        <label for="report_title" class="block text-sm font-semibold text-slate-700 mb-1">
                            Report Title / Description <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="report_title"
                            name="report_title"
                            placeholder="e.g. Complete Blood Count (CBC) or Chest X-Ray"
                            value="{{ old('report_title') }}"
                            required
                            class="w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                        >
                        @error('report_title')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Assigned Doctor -->
                    <div>
                        <label for="doctor_id" class="block text-sm font-semibold text-slate-700 mb-1">
                            Assign to Doctor <span class="text-rose-500">*</span>
                        </label>
                        <select
                            id="doctor_id"
                            name="doctor_id"
                            required
                            class="w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                        >
                            <option value="">Select Doctor</option>
                            @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}" {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                    <x-doctor-name :name="$doctor->name" /> ({{ $doctor->doctorProfile?->specialty ?? 'General Physician' }})
                                </option>
                            @endforeach
                        </select>
                        @error('doctor_id')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- File Upload Input -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">
                            Lab Report Document (PDF, PNG, JPG) <span class="text-rose-500">*</span>
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl hover:border-teal-400 transition bg-slate-50/50">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-slate-600 justify-center">
                                    <label for="report_file" class="relative cursor-pointer bg-white rounded-md font-semibold text-teal-600 hover:text-teal-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-teal-500 px-2 py-0.5">
                                        <span>Select file from computer</span>
                                        <input id="report_file" name="report_file" type="file" class="sr-only" required accept=".pdf,.jpg,.jpeg,.png">
                                    </label>
                                </div>
                                <p class="text-xs text-slate-500">PDF, JPG, PNG up to 10MB (stored securely on private disk)</p>
                            </div>
                        </div>
                        @error('report_file')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <a href="{{ route('patient.timeline') }}" class="px-4 py-2 border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                            Upload and Issue Consent
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
