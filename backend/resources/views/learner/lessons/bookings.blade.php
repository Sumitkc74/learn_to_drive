@extends('learner.layout')
@section('title', __('My driving bookings'))
@section('content')
<div class="page-heading"><h1>{{ __('My driving bookings') }}</h1><a href="{{ route('learn.lessons') }}">{{ __('Book a private driving lesson') }}</a></div>
<p>{{ __(config('schools.payments_enabled') ? 'Pay online using your booking receipt, or arrange payment with the school. Do not pay twice.' : 'Arrange payment directly with the school. Check this page for booking status before travelling.') }}</p>
@forelse($bookings as $booking)
<article class="card">@include('learner.lessons.details', ['lesson' => $booking->lesson])
    <p><strong>{{ __($booking->status) }}</strong></p>
    @php($receipt=$booking->payment)
    @if($receipt)<p><a href="{{ route('learn.lesson-payment.show',$receipt->id) }}">{{ __('Lesson receipt') }} · {{ __($receipt->status) }}</a></p>
    @elseif(config('schools.payments_enabled') && $booking->status==='Booked' && $booking->lesson->price_npr>=10 && $booking->lesson->starts_at->isFuture())
    <form method="post" action="{{ route('learn.lesson-payment.checkout',$booking->id) }}">@csrf<select name="gateway">@foreach(['khalti'=>'Khalti','esewa'=>'eSewa'] as $value=>$label)@if(app(\App\Services\SchoolLessonGateway::class)->ready($value))<option value="{{ $value }}">{{ $label }}</option>@endif @endforeach</select><button>{{ __('Pay online') }}</button></form>
    @endif
    @if($booking->lesson->vehicle->school->phone)<p>{{ __('School phone') }}: {{ $booking->lesson->vehicle->school->phone }}</p>@endif
    @if($booking->status === 'Booked' && $booking->lesson->starts_at->isFuture())
        <p><a href="{{ route('learn.lessons.reschedule',$booking->id) }}">{{ __('Reschedule lesson') }}</a></p>
        @unless($booking->provider_available)<p role="alert">{{ __('This provider is currently unavailable. Contact the school or cancel your booking.') }}</p>@endunless
        <details><summary>{{ __('Cancel booking') }}</summary><form method="post" action="{{ route('learn.lessons.cancel', $booking->id) }}">@csrf @method('DELETE')<button class="button">{{ __('Confirm cancellation') }}</button></form></details>
    @endif
</article>
@empty<p>{{ __('You have no driving lesson bookings yet.') }}</p>@endforelse
{{ $bookings->links('pagination::simple-default') }}
@endsection
