@extends('learner.layout')
@section('title',__('Lesson receipt'))
@section('content')
<section class="card"><h1>{{ __('Lesson receipt') }}</h1><p>{{ $payment->id }}</p><p>NPR {{ number_format($payment->amount_paisa/100,2) }} · {{ $payment->gateway }} · {{ __($payment->status) }}</p>
@unless($payment->live)<p>{{ __('Test payment — no live settlement.') }}</p>@endunless
<p>{{ __('Cancellation before the lesson starts requests a full refund of a verified online payment. Refunds are processed through the merchant and are not instant.') }}</p>
@if($payment->status==='Pending')
@if($payment->booking->status==='Booked' && $payment->booking->lesson->starts_at->isFuture())<form method="post" action="{{ route('learn.lesson-payment.start',$payment->id) }}">@csrf<button class="button">{{ __('Continue to payment provider') }}</button></form>@endif
<form method="post" action="{{ route('learn.lesson-payment.verify',$payment->id) }}">@csrf<button>{{ __('Verify payment') }}</button></form>
@endif
@if($payment->refund_reference)<p>{{ __('Refund reference') }}: {{ $payment->refund_reference }}</p>@endif
<a href="{{ route('learn.lessons.bookings') }}">{{ __('My driving bookings') }}</a></section>
@endsection
