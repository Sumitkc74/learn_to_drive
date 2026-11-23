{{-- Last source check per source ($lastChecks keyed by source, $sourceLabels [key => name]).
     Row dates only change when a NEW row is created, so without this line a
     page looks dead for weeks while the daily check runs fine. --}}
@foreach($sourceLabels as $key => $label)
    @php($run = $lastChecks->get($key))
    <p class="text-muted mb-1">{{ $label }} — last checked: @if($run) {{ \Illuminate\Support\Carbon::parse($run->finished_at ?? $run->started_at)->format('M j, Y g:i A') }} — {{ $run->status }}@if($run->status === 'Success'), {{ $run->added }} new @endif @else not checked yet @endif</p>
@endforeach
