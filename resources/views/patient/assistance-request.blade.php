<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
                    {{ __('Request Medical Assistance') }}
                </h2>
                <p class="text-sm text-slate-500">Contact our clinical helpdesk or send a direct assistance inquiry to your physician.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Assistance Help Banner -->
            <div class="bg-amber-50 rounded-2xl p-6 border border-amber-200 text-amber-900">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <h4 class="font-bold text-sm text-amber-950">Non-Emergency Inquiries Only</h4>
                        <p class="text-xs text-amber-800 mt-1 leading-relaxed">
                            For life-threatening emergencies, please call your local emergency services immediately (911 / 999). This portal is intended for medical coordination, prescription queries, and follow-up guidance.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Assistance Request Form -->
            <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
                <form method="POST" action="{{ route('patient.assistance-requests.store') }}" class="space-y-6">
                    @csrf

                    <!-- Assigned Doctor (Optional) -->
                    <div>
                        <label for="doctor_id" class="block text-sm font-semibold text-slate-700 mb-1">
                            Assign to Doctor (Optional)
                        </label>
                        <select
                            id="doctor_id"
                            name="doctor_id"
                            class="w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                        >
                            <option value="">General Medical Staff / Triage</option>
                            @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}" {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                    Dr. {{ $doctor->name }} ({{ $doctor->doctorProfile?->specialty ?? 'General Practitioner' }})
                                </option>
                            @endforeach
                        </select>
                        @error('doctor_id')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Inquiry Message -->
                    <div>
                        <label for="message" class="block text-sm font-semibold text-slate-700 mb-1">
                            Your Message / Medical Concern <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            id="message"
                            name="message"
                            rows="5"
                            required
                            placeholder="Please describe your symptoms, questions about existing prescriptions, or appointment follow-up details..."
                            class="w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                        >{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <a href="{{ route('patient.dashboard') }}" class="px-4 py-2 border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                            Submit Assistance Request
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
