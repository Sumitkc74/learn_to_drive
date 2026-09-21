@extends('learner.layout')
@section('title',__('Payment history'))
@section('content')
<div class="page-heading"><a href="{{ route('learn.premium') }}">{{ __('← Premium') }}</a><h1>{{ __('Payment history') }}</h1><p>{{ __('View your purchases and check interrupted payments.') }}</p></div><section class="card">@forelse($payments as $payment)<a class="notice-row" href="{{ route('learn.payments.show',$payment) }}"><span>{{ $payment->created_at->locale(app()->getLocale())->translatedFormat('d M Y') }}</span><strong>{{ __('NPR') }} {{ number_format($payment->amount_paisa/100,2) }} · {{ ucfirst($payment->gateway) }} {{ $payment->live?'':__('(test)') }}</strong><span>{{ __($payment->status) }} →</span></a>@empty<p>{{ __('No payment attempts yet.') }}</p>@endforelse{{ $payments->links('pagination::simple-default') }}</section>
@endsection
