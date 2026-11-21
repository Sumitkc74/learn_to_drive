@unless($hideLogo ?? false)
@if($lesson->vehicle->school->logoUrl())<img class="school-logo-small" src="{{ $lesson->vehicle->school->logoUrl() }}" alt="{{ $lesson->vehicle->school->name }} logo" loading="lazy">@else<span class="school-logo-fallback-small" aria-hidden="true">{{ mb_strtoupper(mb_substr($lesson->vehicle->school->name, 0, 1)) }}</span>@endif
@endunless
<div class="lesson-time"><strong>{{ $lesson->starts_at->copy()->timezone('Asia/Kathmandu')->format('d M Y') }}</strong><span>{{ $lesson->starts_at->copy()->timezone('Asia/Kathmandu')->format('H:i') }}–{{ $lesson->ends_at->copy()->timezone('Asia/Kathmandu')->format('H:i') }} {{ __('Nepal time') }}</span></div>
<h2>{{ $lesson->vehicle->name }} · {{ __($lesson->vehicle->transmission) }}</h2>
<dl class="lesson-details">
    <div><dt>{{ __('Instructor') }}</dt><dd>{{ $lesson->vehicle->instructor->name }}</dd></div>
    <div><dt>{{ __('Registration') }}</dt><dd>{{ $lesson->vehicle->registration }}</dd></div>
    <div><dt>{{ __('School') }}</dt><dd>{{ $lesson->vehicle->school->name }}</dd></div>
    <div><dt>{{ __('Meeting point') }}</dt><dd>{{ $lesson->meeting_point }}</dd></div>
    <div><dt>{{ __('Price') }}</dt><dd>NPR {{ number_format($lesson->price_npr) }} · {{ __('One learner per lesson') }}</dd></div>
</dl>
