<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold text-slate-900">Appointment request</h2></x-slot>
    <div class="mx-auto max-w-5xl space-y-5 px-4 py-8">
        @if(session('status'))<p class="rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm text-teal-800">{{ session('status') }}</p>@endif
        @error('appointment')<p class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $message }}</p>@enderror
        @include('receptionist.partials.appointment-requests', ['requests' => collect([$appointment])])
        <a href="{{ route('receptionist.appointments.index') }}" class="inline-block font-semibold text-teal-700">All hospital appointments</a>
    </div>
</x-app-layout>
