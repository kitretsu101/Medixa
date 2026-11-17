@extends('patient.pdf.layout')
@section('title', 'Prescription')
@section('content')
    <h2>Prescription #{{ $prescription->id }}</h2>
    <p class="muted">Issued: {{ $prescription->created_at->format('M d, Y h:i A') }}</p>
    <p><strong>Doctor:</strong> {{ \App\Support\DoctorName::format($prescription->doctor?->name ?? 'N/A') }}</p>
    <div class="record">
        <p><strong>Diagnosis:</strong> {{ $prescription->diagnosis }}</p>
        @include('patient.pdf.medicines', ['medicines' => $prescription->medicines])
        @if(filled($prescription->instructions))
            <p><strong>Clinical instructions</strong></p>
            <p class="instructions">{{ $prescription->instructions }}</p>
        @endif
    </div>
@endsection
