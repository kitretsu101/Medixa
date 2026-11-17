@if(is_array($medicines) && count($medicines))
    <p><strong>Prescribed medicines</strong></p>
    @foreach($medicines as $medicine)
        <p class="medicine">
            <strong>{{ $medicine['name'] ?? 'Medicine' }}</strong>
            @if(!empty($medicine['dosage'])) · {{ $medicine['dosage'] }} @endif
            @if(!empty($medicine['frequency'])) · {{ $medicine['frequency'] }} @endif
            @if(!empty($medicine['duration'])) · {{ $medicine['duration'] }} @endif
        </p>
    @endforeach
@endif
