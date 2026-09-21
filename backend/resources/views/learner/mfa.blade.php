@extends('learner.layout')
@section('title',__('Two-step verification'))
@section('content')
<div class="auth-panel card"><p class="eyebrow">{{ __('ONE MORE STEP') }}</p><h1>{{ __('Verify your sign-in') }}</h1><p>{{ __('Enter the six-digit code from your authenticator app, or one of your saved recovery codes.') }}</p><form class="stack" method="post" action="{{ route('learn.mfa.verify') }}">@csrf<label>{{ __('Authenticator or recovery code') }}<input name="code" required maxlength="64" autocomplete="one-time-code" autofocus></label><button class="button">{{ __('Verify and continue') }}</button></form><form method="post" action="{{ route('learn.logout') }}" class="logout">@csrf<button class="button secondary">{{ __('Cancel sign-in') }}</button></form></div>
@endsection
