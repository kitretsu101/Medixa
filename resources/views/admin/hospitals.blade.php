<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold text-slate-900">Hospitals and assignments</h2></x-slot>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if(session('success'))<p class="rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm text-teal-800">{{ session('success') }}</p>@endif
        @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <p class="text-sm text-slate-600">Historical records remain unassigned until you explicitly assign them. Receptionists cannot manage unassigned appointments.</p>
        <form method="POST" action="{{ route('admin.hospitals.store') }}" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-end">
            @csrf
            <div class="flex-1"><label for="hospital-name" class="block text-sm font-semibold text-slate-700">Hospital name</label><input id="hospital-name" name="name" value="{{ old('name') }}" required maxlength="255" class="mt-1 w-full rounded-lg border-slate-300"></div>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Add hospital</button>
        </form>
        <section class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <h3 class="p-5 text-lg font-bold text-slate-900">Doctor and receptionist assignments</h3>
            <table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-slate-500"><tr><th class="p-4">Staff</th><th class="p-4">Role</th><th class="p-4">Hospital assignment</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($staff as $member)
                <tr><td class="p-4">{{ $member->name }}</td><td class="p-4">{{ ucfirst($member->role) }}</td><td class="p-4">
                    <form method="POST" action="{{ route('admin.hospitals.staff.assign', $member) }}" class="flex flex-wrap gap-2">@csrf
                        <label class="sr-only" for="staff-hospital-{{ $member->id }}">Hospital for {{ $member->name }}</label>
                        <select id="staff-hospital-{{ $member->id }}" name="hospital_id" class="max-w-full rounded-lg border-slate-300 text-sm"><option value="">Unassigned</option>@foreach($hospitals as $hospital)<option value="{{ $hospital->id }}" @selected((int) $member->hospital_id === $hospital->id)>{{ $hospital->name }}</option>@endforeach</select>
                        <button class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Save assignment</button>
                    </form>
                </td></tr>
            @empty<tr><td colspan="3" class="p-5 text-slate-500">No doctors or receptionists yet.</td></tr>@endforelse
            </tbody></table>
        </section>
        {{ $staff->links() }}
        <section class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <h3 class="p-5 text-lg font-bold text-slate-900">Unassigned historical appointments</h3>
            <table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-slate-500"><tr><th class="p-4">Patient</th><th class="p-4">Doctor / date</th><th class="p-4">Status</th><th class="p-4">Explicit hospital assignment</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($unassignedAppointments as $appointment)
                <tr><td class="p-4">{{ $appointment->patient?->name }}</td><td class="p-4">{{ $appointment->doctor?->name }}<br>{{ $appointment->appointment_date?->format('d M Y') }}</td><td class="p-4">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</td><td class="p-4">
                    @if($appointment->doctor?->hospital_id)
                    <form method="POST" action="{{ route('admin.hospitals.appointments.assign', $appointment) }}" class="flex flex-wrap gap-2">@csrf
                        <label class="sr-only" for="appointment-hospital-{{ $appointment->id }}">Hospital for appointment {{ $appointment->id }}</label>
                        <select id="appointment-hospital-{{ $appointment->id }}" name="hospital_id" required class="max-w-full rounded-lg border-slate-300 text-sm"><option value="{{ $appointment->doctor->hospital_id }}">{{ $appointment->doctor->hospital?->name }}</option></select>
                        <button class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Assign appointment</button>
                    </form>
                    @else<span class="text-sm text-amber-800">Assign this doctor's hospital first.</span>@endif
                </td></tr>
            @empty<tr><td colspan="4" class="p-5 text-slate-500">No unassigned appointments.</td></tr>@endforelse
            </tbody></table>
        </section>
        {{ $unassignedAppointments->links() }}
    </div>
</x-app-layout>
