<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
                    {{ __('Patient Medical Profile') }}
                </h2>
                <p class="text-sm text-slate-500">Update your personal medical details and emergency contact records.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @include('patient.partials.live-serial')

            @if(session('status'))
                <div class="rounded-xl bg-teal-50 border border-teal-200 p-4 flex items-center gap-3 text-teal-800 text-sm shadow-sm">
                    <svg class="w-5 h-5 text-teal-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <!-- Profile Summary Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="bg-gradient-to-r from-teal-700 to-indigo-800 px-6 py-6 text-white flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-white text-2xl font-bold border border-white/20">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">{{ $user->name }}</h3>
                        <p class="text-sm text-teal-100">{{ $user->email }} &bull; Role: <span class="capitalize font-semibold">{{ $user->role }}</span></p>
                    </div>
                </div>

                <form method="POST" action="{{ route('patient.profile.update') }}" class="p-6 sm:p-8 space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Date of Birth -->
                        <div>
                            <label for="dob" class="block text-sm font-semibold text-slate-700 mb-1">
                                Date of Birth
                            </label>
                            <input
                                type="date"
                                id="dob"
                                name="dob"
                                value="{{ old('dob', $profile->dob ? $profile->dob->format('Y-m-d') : '') }}"
                                class="w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                            >
                            @error('dob')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Blood Group -->
                        <div>
                            <label for="blood_group" class="block text-sm font-semibold text-slate-700 mb-1">
                                Blood Group
                            </label>
                            <select
                                id="blood_group"
                                name="blood_group"
                                class="w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                            >
                                <option value="">Select Blood Group</option>
                                @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                                    <option value="{{ $bg }}" {{ old('blood_group', $profile->blood_group) === $bg ? 'selected' : '' }}>
                                        {{ $bg }}
                                    </option>
                                @endforeach
                            </select>
                            @error('blood_group')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-semibold text-slate-700 mb-1">Phone</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $profile->phone) }}" class="w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500 text-sm">
                        @error('phone')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nid" class="block text-sm font-semibold text-slate-700 mb-1">National ID</label>
                        <input type="text" id="nid" name="nid" value="{{ old('nid', $profile->nid) }}" class="w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500 text-sm">
                        @error('nid')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Emergency Contact -->
                    <div>
                        <label for="emergency_contact" class="block text-sm font-semibold text-slate-700 mb-1">
                            Emergency Contact Number
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <input
                                type="text"
                                id="emergency_contact"
                                name="emergency_contact"
                                placeholder="+1 (555) 000-0000"
                                value="{{ old('emergency_contact', $profile->emergency_contact) }}"
                                class="w-full pl-10 rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                            >
                        </div>
                        @error('emergency_contact')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <a href="{{ route('patient.dashboard') }}" class="px-4 py-2 border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                            Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
