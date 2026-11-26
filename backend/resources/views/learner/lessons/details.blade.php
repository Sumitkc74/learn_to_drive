@unless($hideLogo ?? false)
@if($lesson->vehicle->school->logoUrl())<img class="school-logo-small" src="{{ $lesson->vehicle->school->logoUrl() }}" alt="{{ $lesson->vehicle->school->name }} logo" loading="lazy">@else<span class="school-logo-fallback-small" aria-hidden="true">{{ mb_strtoupper(mb_substr($lesson->vehicle->school->name, 0, 1)) }}</span>@endif
@endunless
<div class="lesson-values">
    <span class="lesson-when"><strong>{{ $lesson->starts_at->copy()->timezone('Asia/Kathmandu')->format('d M Y') }}</strong><span>{{ $lesson->starts_at->copy()->timezone('Asia/Kathmandu')->format('H:i') }}–{{ $lesson->ends_at->copy()->timezone('Asia/Kathmandu')->format('H:i') }} {{ __('Nepal time') }}</span></span>
    <span class="lesson-car">{{ $lesson->vehicle->name }} · {{ __($lesson->vehicle->transmission) }}@unless($hideVehicleRegistration ?? false) · {{ $lesson->vehicle->registration }}@endunless</span>
    <span>{{ $lesson->vehicle->instructor->name }}</span>
    <span>{{ $lesson->vehicle->school->name }}@if($lesson->displayMeetingPoint() !== '') · {{ $lesson->displayMeetingPoint() }}@endif</span>
    <span class="lesson-price">Rs {{ number_format($lesson->price_npr) }}</span>
</div>
