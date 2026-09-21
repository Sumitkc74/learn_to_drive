@extends('learner.layout')
@section('title',__($reset?'Reset password':'Forgot password'))
@section('content')
<div class="auth-panel card"><a href="{{ route('learn.login') }}">{{ __('← Sign in') }}</a><h1>{{ __($reset?'Choose a new password':'Forgot your password?') }}</h1><p>{{ __($reset?'Enter your new password below.':'Enter your account email and we will send you a link to reset your password.') }}</p>
<form class="stack" method="post" action="{{ route($reset?'learn.password.update':'learn.password.email') }}">@csrf
@if($reset)<input type="hidden" name="token" value="{{ $token }}">@endif
<label>{{ __('Email address') }}<input type="email" name="email" required autocomplete="email" value="{{ old('email',$email ?? '') }}"></label>
@if($reset)<label>{{ __('New password') }}<input type="password" name="password" required minlength="8" autocomplete="new-password"></label><small class="field-help">{{ __('Use at least 8 characters.') }}</small><label>{{ __('Confirm new password') }}<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>@endif
<button class="button">{{ __($reset?'Reset password':'Send reset link') }}</button></form></div>
@endsection
