@extends('learner.layout')
@section('title',__('Continue to eSewa'))
@section('content')
<div class="auth-panel card"><h1>{{ __('Continue to eSewa') }}</h1><p>{{ __('Premium for') }} {{ $payment->access_days }} {{ __('days') }}</p><p><strong>{{ __('NPR') }} {{ number_format($payment->amount_paisa/100,2) }}</strong></p>@unless($payment->live)<p>{{ __('Sandbox checkout — test payments only.') }}</p>@endunless
<form action="{{ $action }}" method="post">@foreach($fields as $name=>$value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach<button class="button">{{ __('Continue to eSewa') }}</button></form><p><a href="{{ route('learn.payments.show',$payment) }}">{{ __('Back to payment details') }}</a></p></div>
@endsection
