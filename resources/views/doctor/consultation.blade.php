<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Consultation Session</h2>
                <p class="mt-1 text-sm text-gray-500">Today's queue · Current serial #{{ $queue->current_running_serial }}</p>
            </div>
            <a href="{{ route('doctor.dashboard') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">Return to dashboard</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        @if(session('consultation_status'))
            <p role="status" class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">{{ session('consultation_status') }}</p>
        @endif
        @if($appointment?->patient !== null)
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm" aria-labelledby="current-patient">
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Current patient · Serial #{{ $appointment->serial_number }}</p>
                <h1 id="current-patient" class="mt-2 break-words text-2xl font-bold text-gray-900">{{ $appointment->patient->name }}</h1>
                <p class="mt-2 text-sm text-gray-500">{{ $appointment->appointment_date->format('d M Y') }} · {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</p>
                <p class="mt-2 text-xs text-gray-500">Clinical profile fields stay hidden unless a consent token covers them.</p>
            </section>
            <section aria-labelledby="consultation-history-heading">
                <h2 id="consultation-history-heading" class="mt-8 text-xl font-bold text-gray-900">Clinical History · Consent-Scoped Access</h2>
                {!! $history !!}
            </section>
            <section aria-labelledby="consultation-prescription-heading">
                <h2 id="consultation-prescription-heading" class="text-xl font-bold text-gray-900">Digital Prescription Builder</h2>
                {!! $prescriptionForm !!}
            </section>
        @else
            <section class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                <h1 class="text-xl font-bold text-gray-900">No patient currently in queue</h1>
                <p class="mt-2 text-sm text-gray-500">No appointment matches today's current serial. Use Next Patient to advance when patients are waiting.</p>
            </section>
        @endif

        <form method="POST" action="{{ route('doctor.consultation.next') }}" class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white p-5">
            @csrf
            <p class="text-sm text-gray-500">You can move on without saving a prescription.</p>
            <button type="submit" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">Next Patient</button>
        </form>
    </div>
</x-app-layout>
