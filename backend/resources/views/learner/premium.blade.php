@extends('learner.layout')
@section('title',__('Premium'))
@section('content')
@php($premium=auth()->check() && auth()->user()->hasPremium())
@guest
@include('learner.tier-comparison')
@if(config('payments.enabled'))<p class="muted"><a href="{{ route('learn.register', ['premium' => 1]) }}">{{ __('Create an account') }}</a> {{ __('to upgrade to Premium.') }}</p>@endif
@else
<div class="page-heading"><p class="eyebrow">{{ __('LEARN FROM EVERY MISTAKE') }}</p><h1>{{ __('A little extra support.') }}<br>{{ __('A clearer way forward.') }}</h1><p class="lead">{{ __('Premium brings your difficult topics together and gives you a study assistant to help explain them.') }}</p></div>
@if($premium)<p class="badge">{{ __('Premium access active') }}</p>@endif
@endguest
<p class="muted">{{ __('Students enrolled at an approved driving school get Premium while they are enrolled — no separate payment needed.') }}</p>
@auth
<div id="premium-checkout">@include('learner.payment-options')</div>
@endauth
<p class="muted premium-note">{{ __('Regular practice and the learning library remain available without Premium. AI explanations can be mistaken; refer to published resources and current official guidance.') }}</p>
@endsection
