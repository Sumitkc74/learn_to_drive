<h2>{{ $lesson->vehicle->name }} · {{ __($lesson->vehicle->transmission) }}</h2>
<p>{{ __('Instructor') }}: {{ $lesson->vehicle->instructor->name }} · {{ $lesson->vehicle->school->name }}</p>
<p>{{ $lesson->starts_at->copy()->timezone('Asia/Kathmandu')->format('d M Y, H:i') }}–{{ $lesson->ends_at->copy()->timezone('Asia/Kathmandu')->format('H:i') }} {{ __('Nepal time') }}</p>
<p>{{ __('Meeting point') }}: {{ $lesson->meeting_point }}</p>
<p>{{ __('Price') }}: NPR {{ number_format($lesson->price_npr) }} · {{ __('One learner per lesson') }}</p>
