<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Walk-in</h2>
            <p class="text-sm text-slate-500">Book an existing patient and check them in if the visit is today.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto px-4">
            <form method="POST" action="{{ route('receptionist.walk-in.store') }}" class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
                @csrf
                <div>
                    <label for="patient_email" class="block text-sm font-semibold text-slate-700">Patient email</label>
                    <input id="patient_email" name="patient_email" type="email" value="{{ old('patient_email') }}" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    @error('patient_email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="doctor_id" class="block text-sm font-semibold text-slate-700">Doctor</label>
                    <select id="doctor_id" name="doctor_id" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Select</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" @selected((int) old('doctor_id') === (int) $doctor->id)>{{ $doctor->name }} — {{ $doctor->doctorProfile?->department?->name }}</option>
                        @endforeach
                    </select>
                    @error('doctor_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="priority_type" class="block text-sm font-semibold text-slate-700">Priority</label>
                    <select id="priority_type" name="priority_type" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        @foreach(['normal' => 'Normal', 'senior' => 'Senior citizen', 'follow_up' => 'Follow-up', 'emergency' => 'Emergency'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority_type', 'normal') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="appointment_date" class="block text-sm font-semibold text-slate-700">Date</label>
                    <input id="appointment_date" name="appointment_date" type="date" value="{{ old('appointment_date', now()->toDateString()) }}" min="{{ now()->toDateString() }}" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    @error('appointment_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg bg-teal-700 text-white text-sm font-semibold">Create token</button>
            </form>
        </div>
    </div>
</x-app-layout>
