<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-slate-900 leading-tight">
                    {{ $doctor ? __('Edit Doctor Profile') : __('Register New Doctor') }}
                </h2>
                <p class="text-sm text-slate-500 mt-1">Configure doctor credentials, clinical department, and qualifications</p>
            </div>
            <a href="{{ route('admin.doctors.index') }}" class="px-4 py-2 border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-xl transition">
                &larr; Back to Doctors
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">
                <form method="POST" action="{{ $doctor ? route('admin.doctors.update', $doctor) : route('admin.doctors.store') }}" class="space-y-6">
                    @csrf
                    @if($doctor)
                        @method('PUT')
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Full Name -->
                        <div>
                            <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">
                                Full Name <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name', $doctor?->name) }}"
                                placeholder="e.g. Dr. Arman Hossain"
                                class="w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                                required
                            >
                            @error('name')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email Address -->
                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700 mb-1">
                                Email Address <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email', $doctor?->email) }}"
                                placeholder="doctor@medixa.test"
                                class="w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                                required
                            >
                            @error('email')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if(! $doctor)
                        <!-- Password (Only on creation) -->
                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-700 mb-1">
                                Initial Password <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Minimum 8 characters"
                                class="w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                                required
                            >
                            @error('password')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Department -->
                        <div>
                            <label for="department_id" class="block text-sm font-semibold text-slate-700 mb-1">
                                Clinical Department <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="department_id"
                                name="department_id"
                                class="w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                                required
                            >
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $doctor?->doctorProfile?->department_id) == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Designation -->
                        <div>
                            <label for="designation" class="block text-sm font-semibold text-slate-700 mb-1">
                                Designation <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="designation"
                                name="designation"
                                value="{{ old('designation', $doctor?->doctorProfile?->designation) }}"
                                placeholder="e.g. Senior Consultant Cardiologist"
                                class="w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                                required
                            >
                            @error('designation')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Qualification -->
                    <div>
                        <label for="qualification" class="block text-sm font-semibold text-slate-700 mb-1">
                            Qualifications & Degrees <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="qualification"
                            name="qualification"
                            value="{{ old('qualification', $doctor?->doctorProfile?->qualification) }}"
                            placeholder="e.g. MBBS, MD (Cardiology), FCPS, MRCP (UK)"
                            class="w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                            required
                        >
                        @error('qualification')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <a href="{{ route('admin.doctors.index') }}" class="px-4 py-2.5 border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-xl transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                            {{ $doctor ? __('Update Doctor Profile') : __('Register Doctor') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
