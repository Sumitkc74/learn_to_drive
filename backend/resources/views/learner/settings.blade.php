@extends('learner.layout')
@section('title',__('Account settings'))
@section('content')
<div class="page-heading settings-heading"><p class="eyebrow">{{ __('MAKE YOURSELF AT HOME') }}</p><h1>{{ __('Account settings') }}</h1><p class="lead">{{ __('Your personal details and sign-in security, in one place.') }}</p></div>
<div class="settings-layout">
<aside class="settings-sidebar">
<div class="settings-identity"><span class="settings-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name,0,1)) }}</span><strong>{{ $user->name }}</strong><span>{{ __('Member since') }} {{ $user->created_at?->locale(app()->getLocale())->translatedFormat('M Y') }}</span></div>
<nav aria-label="Account settings sections" class="settings-nav"><a href="#personal-details">{{ __('Personal details') }} <span aria-hidden="true">↗</span></a><a href="#password-security">{{ __('Password & security') }} <span aria-hidden="true">↗</span></a><a href="#sign-in-options">{{ __('Connected accounts') }}</a><a href="#two-step-verification">{{ __('Two-step verification') }}</a></nav>
<p class="settings-note">{{ __('Your learning history stays with your account when you update these details.') }}</p>
</aside>
<div class="settings-sections">
<section class="card settings-card" id="personal-details" aria-labelledby="profile-title"><div class="settings-section-heading"><span class="badge">{{ __('PROFILE') }}</span><h2 id="profile-title">{{ __('Personal details') }}</h2><p>{{ __('Keep your name and contact information up to date.') }}</p></div>
@foreach(['name'=>'Full name','email'=>'Email address','phoneNumber'=>'Phone number'] as $field=>$label)
<details class="settings-detail" @if($errors->has($field) || ($field==='email' && $errors->has('current_password') && old('_edit_field')==='email')) open @endif>
<summary><span><strong>{{ __($label) }}</strong><span class="setting-value">{{ $user->$field }}</span></span><span class="text-link">{{ __('Edit') }}</span></summary>
<form class="stack" action="{{ route('learn.settings.detail',$field) }}" method="post">@csrf @method('PATCH')<input type="hidden" name="_edit_field" value="{{ $field }}">
<label for="edit-{{ $field }}">{{ __($label) }}<input id="edit-{{ $field }}" name="{{ $field }}" type="{{ $field==='email'?'email':'text' }}" value="{{ old($field,$user->$field) }}" required maxlength="{{ $field==='name'?100:($field==='email'?255:10) }}" autocomplete="{{ $field==='phoneNumber'?'tel':$field }}" @if($field==='phoneNumber') pattern="[0-9]{10}" inputmode="tel" @endif></label>
@if($field==='email')<label>{{ __('Current password') }}<input type="password" name="current_password" required autocomplete="current-password"></label><small class="field-help">{{ __('Use your new email to sign in after saving. You will need to verify the new address.') }}</small>@elseif($field==='phoneNumber')<small class="field-help">{{ __('Enter 10 digits without spaces or a country code.') }}</small>@endif
<div><button class="button">{{ __('Save :field',['field'=>app()->getLocale()==='en'?strtolower($label):__($label)]) }}</button></div></form></details>
@endforeach
<div class="email-verification"><strong>{{ __('Email verification') }}</strong>@if($user->hasVerifiedEmail())<span class="badge">{{ __('Verified') }}</span><p class="field-help">{{ __('You have confirmed access to') }} {{ $user->email }}.</p>@else<span class="badge">{{ __('Not verified') }}</span><p class="field-help">{{ __('Confirm that you can receive email at') }} {{ $user->email }}{{ __('. This is also the address used for password recovery.') }}</p><form method="post" action="{{ route('learn.verification.send') }}">@csrf<button class="button secondary">{{ __('Send verification email') }}</button></form>@endif</div></section>
<section class="card settings-card" id="password-security" aria-labelledby="password-title"><div class="settings-section-heading"><span class="badge">{{ __('SECURITY') }}</span><h2 id="password-title">{{ __('Change password') }}</h2><p>{{ __('Choose a unique password that you do not use elsewhere.') }}</p></div>
<form class="stack" action="{{ route('learn.settings.password') }}" method="post">@csrf @method('PUT')
<a href="{{ route('learn.password.request') }}">{{ __('Forgot your password?') }}</a><label for="security-current-password">{{ __('Current password') }}<input id="security-current-password" type="password" name="current_password" required autocomplete="current-password"></label>
<div class="settings-fields"><div><label for="new-password">{{ __('New password') }}<input id="new-password" type="password" name="password" required minlength="8" autocomplete="new-password" aria-describedby="password-help"></label><small id="password-help" class="field-help">{{ __('At least 8 characters, different from your current password.') }}</small></div><div><label for="confirm-password">{{ __('Confirm new password') }}<input id="confirm-password" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label><small class="field-help">{{ __('Enter your new password again.') }}</small>
</div></div>
<div class="settings-form-footer"><span>{{ __('Use your new password the next time you sign in.') }}</span><button class="button">{{ __('Change password') }}</button></div></form></section>
@include('learner.security-options')
</div></div>
@endsection