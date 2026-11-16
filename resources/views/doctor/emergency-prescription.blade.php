<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-900">Emergency Prescription Access</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 space-y-6">
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                EMERGENCY OVERRIDE — This access has been logged and the patient has been notified. Future access requires consent or another explicit emergency request.
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-6 space-y-4">
                <h3 class="font-bold text-gray-900">{{ $patient->name }} — Prescription #{{ $prescription->id }}</h3>
                <p class="text-sm text-gray-600">{{ $prescription->created_at->format('M d, Y') }}</p>
                <div>
                    <h4 class="text-xs font-bold uppercase text-gray-500">Diagnosis</h4>
                    <p class="text-sm text-gray-900">{{ $prescription->diagnosis }}</p>
                </div>
                <div>
                    <h4 class="text-xs font-bold uppercase text-gray-500">Medicines</h4>
                    @foreach($prescription->medicines ?? [] as $medicine)
                        <p class="mt-2 text-sm text-gray-900">
                            {{ $medicine['name'] ?? '' }} ({{ $medicine['dosage'] ?? '' }})
                            — {{ $medicine['frequency'] ?? '' }} — {{ $medicine['duration'] ?? '' }}
                        </p>
                    @endforeach
                </div>
                @if($prescription->instructions)
                    <p class="text-sm text-gray-700">Instructions: {{ $prescription->instructions }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
