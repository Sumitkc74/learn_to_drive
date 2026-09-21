@extends('learner.layout')
@section('title',__('Payment status'))
@section('content')
<div class="page-heading"><a href="{{ route('learn.payments') }}">{{ __('← Payment history') }}</a><h1>{{ __('Payment status') }}</h1></div><section class="card detail"><span class="badge">{{ __($payment->status) }}</span><h2>{{ __('NPR') }} {{ number_format($payment->amount_paisa/100,2) }} · {{ $payment->access_days }} {{ __('days') }}</h2><p>{{ __('Gateway:') }} {{ $payment->gateway==='esewa'?'eSewa':'Khalti' }} {{ $payment->live?'':__('(sandbox)') }}</p><p class="prose">{{ __('Reference:') }} {{ $payment->id }}</p>
@if($payment->status==='Paid')<p>{{ __('Payment verified. Access from this purchase was credited through') }} {{ $payment->access_until->timezone('Asia/Kathmandu')->locale(app()->getLocale())->translatedFormat('d M Y, H:i') }} {{ __('Nepal time.') }}</p><a class="button" href="{{ route('learn.premium') }}">{{ __('Open Premium') }}</a>
@else<p>{{ __('If money was deducted, check the status before attempting another payment. A cancelled checkout never activates Premium.') }}</p><form method="post" action="{{ route('learn.payments.verify',$payment) }}">@csrf<button class="button">{{ __('Check payment status') }}</button></form>@endif<p><a href="{{ route('learn.support') }}">{{ __('Need help? Include this reference in your message.') }}</a></p></section>
@endsection
