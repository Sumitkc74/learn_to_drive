@extends('learner.layout')
@section('title',__($register?'Create an account':'Welcome back'))
@section('content')
<div class="auth-panel card"><p class="eyebrow">{{ __('YOUR LEARNING JOURNEY') }}</p><h1>{{ __($register?'Let’s get started.':'Welcome back.') }}</h1><p>{{ __($register?'Save your practice results and keep in touch with our team.':'Sign in to continue practising.') }}</p>@include('learner.google-button')<p class="auth-divider">{{ __('or use your email') }}</p>@include('learner.auth-form')<p>@if($register){{ __('Already learning with us?') }} <a href="{{ route('learn.login') }}">{{ __('Sign in') }}</a>@else {{ __('New here?') }} <a href="{{ route('learn.register') }}">{{ __('Create an account') }}</a>@endif</p>@unless($register)<a href="{{ route('learn.password.request') }}">{{ __('Forgot password?') }}</a>@endunless</div>
@endsection
