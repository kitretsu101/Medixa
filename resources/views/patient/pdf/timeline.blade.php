@extends('patient.pdf.layout')
@section('title', 'Medical Record Timeline')
@section('content')
    <h2>Medical Record Timeline</h2>
    @forelse($timeline as $item)
        <div class="record">
            <p class="muted">{{ $item->type === 'prescription' ? 'Prescription' : 'Lab Report' }} · {{ $item->date?->format('M d, Y h:i A') ?? 'N/A' }}</p>
            <h3>{{ $item->title }}</h3>
            <p><strong>Doctor:</strong> {{ \App\Support\DoctorName::format($item->doctor_name) }}</p>
            @if(filled($item->detail))
                <p class="instructions">{{ $item->detail }}</p>
            @endif
            @if($item->type === 'prescription')
                @include('patient.pdf.medicines', ['medicines' => $item->medicines])
            @endif
        </div>
    @empty
        <p>No medical records yet.</p>
    @endforelse
@endsection
