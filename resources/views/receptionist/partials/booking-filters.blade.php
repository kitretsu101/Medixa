<form method="GET" action="{{ url()->current() }}" class="flex flex-wrap items-end gap-4 rounded-xl border border-slate-200 bg-white p-4">
    <div class="min-w-40 flex-1">
        <label for="booking-doctor" class="mb-1 block text-sm font-semibold text-gray-700">Doctor</label>
        <select id="booking-doctor" name="doctor_id" class="w-full rounded-lg border border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
            <option value="">All Doctors</option>
            @foreach($doctors as $doctor)<option value="{{ $doctor->id }}" @selected((int) request('doctor_id') === (int) $doctor->id)>{{ $doctor->name }}</option>@endforeach
        </select>
    </div>
    <div>
        <label for="booking-date" class="mb-1 block text-sm font-semibold text-gray-700">Appointment date</label>
        <input id="booking-date" type="date" name="appointment_date" value="{{ request('appointment_date') }}" class="rounded-lg border border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
    </div>
    <div>
        <label for="booking-sort" class="mb-1 block text-sm font-semibold text-gray-700">Sort by</label>
        <select id="booking-sort" name="sort" class="rounded-lg border border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
            @foreach(['pending' => 'Pending first', 'appointment_newest' => 'Appointment date: newest first', 'appointment_oldest' => 'Appointment date: oldest first', 'doctor' => 'Doctor name', 'created_newest' => 'Request received: newest first', 'created_oldest' => 'Request received: oldest first'] as $value => $label)
                <option value="{{ $value }}" @selected(request('sort', $defaultSort ?? 'pending') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button class="rounded-lg bg-teal-700 px-4 py-2 font-semibold text-white hover:bg-teal-800">Apply filters</button>
    <a href="{{ url()->current() }}" class="rounded-lg border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">Clear</a>
    @foreach(['doctor_id', 'appointment_date', 'sort'] as $field)
        @error($field)
            <p class="w-full text-sm text-rose-700">{{ $message }}</p>
        @enderror
    @endforeach
</form>
