<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Book an appointment</h2>
            <p class="text-sm text-slate-500">Choose a doctor and a date. Your serial number is assigned on the server.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto px-4">
            <form method="POST" action="{{ route('patient.appointments.store') }}" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5">
                @csrf
                <div>
                    <label for="doctor_id" class="block text-sm font-semibold text-slate-700 mb-1">Doctor</label>
                    <select id="doctor_id" name="doctor_id" class="w-full rounded-xl border-slate-300 text-sm">
                        <option value="">Select a doctor</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" @selected((int) old('doctor_id', $selectedDoctorId) === (int) $doctor->id)>
                                <x-doctor-name :name="$doctor->name" /> — {{ $doctor->doctorProfile?->department?->name ?? 'OPD' }}
                            </option>
                        @endforeach
                    </select>
                    @error('doctor_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="priority_type" class="block text-sm font-semibold text-slate-700 mb-1">Visit priority</label>
                    <select id="priority_type" name="priority_type" class="w-full rounded-xl border-slate-300 text-sm">
                        <option value="normal" @selected(old('priority_type', 'normal') === 'normal')>Normal consultation</option>
                        <option value="senior" @selected(old('priority_type') === 'senior')>Senior citizen</option>
                        <option value="follow_up" @selected(old('priority_type') === 'follow_up')>Follow-up</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Emergency visits are booked at the desk so a buffer slot stays available. Long waits still move forward.</p>
                    @error('priority_type')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="appointment_date" class="block text-sm font-semibold text-slate-700 mb-1">Date</label>
                    <input type="date" id="appointment_date" name="appointment_date" value="{{ old('appointment_date', now()->addDay()->toDateString()) }}" min="{{ now()->toDateString() }}" class="w-full rounded-xl border-slate-300 text-sm">
                    @error('appointment_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg disabled:opacity-60" onclick="this.disabled=true; this.form.submit();">Confirm booking</button>
            </form>
        </div>
    </div>
</x-app-layout>
