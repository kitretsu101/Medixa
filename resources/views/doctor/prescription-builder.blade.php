<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    Digital Prescription Builder
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    OPD Consultation &bull; Serial #{{ str_pad($appointment->serial_number, 3, '0', STR_PAD_LEFT) }} &bull; Appointment Date: {{ $appointment->appointment_date ? $appointment->appointment_date->format('M d, Y') : 'Today' }}
                </p>
            </div>
            <a href="{{ route('doctor.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg text-xs font-semibold uppercase tracking-wider transition">
                &larr; Cancel / Return
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Patient Info Card -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Patient Details</span>
                        <h3 class="text-xl font-bold text-gray-900 mt-1">{{ $appointment->patient?->name }}</h3>
                        <p class="text-xs text-gray-500 font-mono">{{ $appointment->patient?->email }}</p>
                    </div>
                    <div class="flex flex-wrap gap-4 text-sm bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div>
                            <span class="text-xs text-gray-500 block">Blood Group</span>
                            <span class="font-bold text-gray-900">{{ $appointment->patient?->patientProfile?->blood_group ?? 'N/A' }}</span>
                        </div>
                        <div class="border-l border-gray-200 pl-4">
                            <span class="text-xs text-gray-500 block">DOB / Age</span>
                            <span class="font-bold text-gray-900">
                                {{ $appointment->patient?->patientProfile?->dob ? $appointment->patient->patientProfile->dob->format('M d, Y') : 'Not set' }}
                            </span>
                        </div>
                        <div class="border-l border-gray-200 pl-4">
                            <span class="text-xs text-gray-500 block">Emergency Contact</span>
                            <span class="font-bold text-gray-900">{{ $appointment->patient?->patientProfile?->emergency_contact ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                @if($pendingReports->isNotEmpty())
                    <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>This patient has <strong>{{ $pendingReports->count() }} pending lab report(s)</strong> awaiting review. Submitting this prescription will automatically mark them as <strong>Reviewed</strong>.</span>
                        </div>
                        <a href="{{ route('doctor.pending-reviews') }}" class="font-bold underline text-amber-900 hover:text-amber-700 ml-2 shrink-0">
                            Check Reports
                        </a>
                    </div>
                @endif
            </div>

            <!-- Prescription Form -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6 sm:p-8">
                <form method="POST" action="{{ route('doctor.prescriptions.store', $appointment) }}" class="space-y-6">
                    @csrf

                    @if ($errors->any())
                        <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm space-y-1">
                            <div class="font-bold">Please correct the errors below:</div>
                            <ul class="list-disc list-inside text-xs">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Diagnosis -->
                    <div>
                        <label for="diagnosis" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Clinical Diagnosis <span class="text-red-500">*</span>
                        </label>
                        <textarea id="diagnosis" name="diagnosis" rows="3" required
                                  placeholder="e.g. Acute Viral Bronchitis, mild dehydration, normotensive."
                                  class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 text-sm transition">{{ old('diagnosis') }}</textarea>
                    </div>

                    <!-- Dynamic Medicines Builder (Alpine.js) -->
                    <div x-data="medicinesBuilder()" class="space-y-3 pt-2">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <div>
                                <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Prescribed Medications <span class="text-red-500">*</span>
                                </h4>
                                <p class="text-xs text-gray-500">Add drug formulations, dosages, intake intervals, and durations.</p>
                            </div>
                            <button type="button" @click="addRow()" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-bold transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Medicine
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(medicine, index) in medicines" :key="index">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200 items-end">
                                    <div class="sm:col-span-4">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Medicine Name</label>
                                        <input type="text" :name="`medicines[${index}][name]`" x-model="medicine.name" required
                                               placeholder="e.g. Amoxicillin"
                                               class="w-full rounded-lg border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-200">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Dosage</label>
                                        <input type="text" :name="`medicines[${index}][dosage]`" x-model="medicine.dosage" required
                                               placeholder="e.g. 500mg"
                                               class="w-full rounded-lg border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-200">
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Frequency</label>
                                        <input type="text" :name="`medicines[${index}][frequency]`" x-model="medicine.frequency"
                                               placeholder="e.g. 1-0-1 (After meal)"
                                               class="w-full rounded-lg border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-200">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Duration</label>
                                        <input type="text" :name="`medicines[${index}][duration]`" x-model="medicine.duration"
                                               placeholder="e.g. 7 days"
                                               class="w-full rounded-lg border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-200">
                                    </div>
                                    <div class="sm:col-span-1 flex justify-end">
                                        <button type="button" @click="removeRow(index)" :disabled="medicines.length <= 1"
                                                class="p-2 text-gray-400 hover:text-red-600 disabled:opacity-30 disabled:hover:text-gray-400 transition" title="Remove item">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Instructions / Advice -->
                    <div class="pt-2">
                        <label for="instructions" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Instructions & Clinical Advice
                        </label>
                        <textarea id="instructions" name="instructions" rows="3"
                                  placeholder="e.g. Drink plenty of warm fluids. Bed rest for 3 days. Follow up if fever persists."
                                  class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 text-sm transition">{{ old('instructions') }}</textarea>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs text-gray-500">
                            Saving automatically finalizes the appointment and resolves related pending lab reports.
                        </span>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('doctor.dashboard') }}" class="px-4 py-2.5 text-xs font-semibold text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow-sm transition">
                                Issue Prescription & Complete Visit
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        function medicinesBuilder() {
            return {
                medicines: [
                    { name: '', dosage: '', frequency: '1-0-1', duration: '5 days' }
                ],
                addRow() {
                    this.medicines.push({ name: '', dosage: '', frequency: '1-0-1', duration: '5 days' });
                },
                removeRow(index) {
                    if (this.medicines.length > 1) {
                        this.medicines.splice(index, 1);
                    }
                }
            }
        }
    </script>
</x-app-layout>
