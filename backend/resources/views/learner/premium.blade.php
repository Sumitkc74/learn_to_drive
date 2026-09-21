@extends('learner.layout')
@section('title',__('Premium'))
@section('content')
@guest
@include('learner.tier-comparison')
@else
<div class="page-heading"><p class="eyebrow">{{ __('LEARN FROM EVERY MISTAKE') }}</p><h1>{{ __('A little extra support.') }}<br>{{ __('A clearer way forward.') }}</h1><p class="lead">{{ __('Premium brings your difficult topics together and gives you a study assistant to help explain them.') }}</p></div>
@php($premium=auth()->check() && auth()->user()->hasPremium())
@if($premium)<p class="badge">{{ __('Premium access active') }}</p>@endif
<div class="two-column"><section class="card"><span class="badge">{{ __('ASK & UNDERSTAND') }}</span><h2>{{ __('Your Gemini study coach') }}</h2><p>{{ __('Ask follow-up questions in English or Nepali. Get help understanding concepts and the questions you missed.') }}</p>@if($premium)<a class="button" href="{{ route('learn.premium.chat') }}">{{ __('Open chatbot →') }}</a>@endif</section><section class="card"><span class="badge">{{ __('REVIEW & RETRY') }}</span><h2>{{ __('Personalized practice & revision') }}</h2><p>{{ __('Your incorrect answers become topic-based revision modules. Review the correct solutions, then retry those questions to check your understanding.') }}</p>@if($premium)<a class="button" href="{{ route('learn.premium.modules') }}">{{ __('Build my practice set →') }}</a>@endif</section></div>
@endguest
@php($premium=auth()->check() && auth()->user()->hasPremium())
<div id="premium-checkout">@include('learner.payment-options')</div>
<p class="muted premium-note">{{ __('Regular practice and the learning library remain available without Premium. AI explanations can be mistaken; refer to published resources and current official guidance.') }}</p>
@endsection
