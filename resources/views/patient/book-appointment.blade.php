<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Book an appointment</h2>
            <p class="text-sm text-slate-500">Choose a doctor and a date. Your serial number is assigned on the server.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto px-4">
            <form method="POST" action="{{ route('patient.appointments.store') }}" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                @if($hospitals->isEmpty())
                    <p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">No hospitals are available yet. Please contact reception.</p>
                @endif
                <div>
                    <label for="hospital_id" class="block text-sm font-semibold text-slate-700 mb-1">Hospital</label>
                    <select id="hospital_id" name="hospital_id" required class="w-full rounded-xl border-slate-300 text-sm">
                        <option value="">Select a hospital</option>
                        @foreach($hospitals as $hospital)<option value="{{ $hospital->id }}" @selected((int) old('hospital_id', $doctors->firstWhere('id', $selectedDoctorId)?->hospital_id) === $hospital->id)>{{ $hospital->name }}</option>@endforeach
                    </select>
                    @error('hospital_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-slate-500">New requests remain pending until the hospital receptionist approves them.</p>
                </div>
                <div>
                    <label for="doctor_id" class="block text-sm font-semibold text-slate-700 mb-1">Doctor</label>
                    <select id="doctor_id" name="doctor_id" required class="w-full rounded-xl border-slate-300 text-sm">
                        <option value="">Select a doctor</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" data-hospital-id="{{ $doctor->hospital_id }}" @selected((int) old('doctor_id', $selectedDoctorId) === (int) $doctor->id)>
                                <x-doctor-name :name="$doctor->name" /> — {{ $doctor->doctorProfile?->department?->name ?? 'OPD' }} · {{ $doctor->hospital?->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('doctor_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    <p id="doctor-availability" class="mt-1 text-xs text-slate-500" aria-live="polite"></p>
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
                <div><label for="reason" class="block text-sm font-semibold text-slate-700 mb-1">Appointment reason (optional)</label><textarea id="reason" name="reason" rows="3" maxlength="1000" class="w-full rounded-xl border-slate-300 text-sm">{{ old('reason') }}</textarea>@error('reason')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <button type="submit" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg disabled:opacity-60">Confirm booking</button>
            </form>
        </div>
    </div>
    @push('scripts')
    <script>
        (() => {
            const hospital = document.getElementById('hospital_id');
            const doctor = document.getElementById('doctor_id');
            const status = document.getElementById('doctor-availability');
            const options = Array.from(doctor.options).filter(option => option.value);
            function filterDoctors(reset = false) {
                const selected = reset ? '' : doctor.value;
                const matching = options.filter(option => !hospital.value || option.dataset.hospitalId === hospital.value);
                doctor.replaceChildren(new Option('Select a doctor', ''));
                matching.forEach(option => doctor.appendChild(option));
                doctor.value = matching.some(option => option.value === selected) ? selected : '';
                doctor.disabled = matching.length === 0;
                status.textContent = matching.length ? '' : 'No doctors are assigned to this hospital yet. Please contact reception.';
            }
            hospital.addEventListener('change', () => filterDoctors(true));
            doctor.addEventListener('change', () => {
                const selected = options.find(option => option.value === doctor.value);
                if (selected) { hospital.value = selected.dataset.hospitalId; filterDoctors(); }
            });
            filterDoctors();
        })();
    </script>
    @endpush
</x-app-layout>
