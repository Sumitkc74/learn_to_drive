<!doctype html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>@yield('title','Learn to Drive') · Learn to Drive</title><link rel="stylesheet" href="{{ asset('css/learner.css').'?v='.filemtime(public_path('css/learner.css')) }}"><script src="{{ asset('js/learner-theme.js') }}"></script></head>
<body><a class="skip" href="#main">{{ __('Skip to content') }}</a><header class="header"><div class="nav-wrap"><a class="brand" href="{{ route(auth()->check() ? 'learn.account' : 'learn.home') }}"><img class="brand-logo" src="{{ asset('dist/img/logo.svg') }}" alt=""> Learn to Drive<span class="brand-tag">{{ __('NEPAL') }}</span></a><nav aria-label="{{ __('Main navigation') }}">
@php($libraryActive=request()->routeIs('learn.library.home') || (request()->routeIs('learn.library','learn.detail') && request()->route('type')!=='notice'))
@auth
<a href="{{ route('learn.account') }}" @if(request()->routeIs('learn.account')) aria-current="page" @endif>{{ __('My learning') }}</a>
@else
<a href="{{ route('learn.home') }}">{{ __('Home') }}</a>
@endauth
<a href="{{ route('learn.library.home') }}" @if($libraryActive) aria-current="page" @endif>{{ __('Library') }}</a>
<a href="{{ route('learn.practice') }}" @if(request()->routeIs('learn.practice','learn.practice.*','learn.flashcards')) aria-current="page" @endif>{{ __('Practice') }}</a>
<a href="{{ route('learn.library','notice') }}" @if(request()->route('type')==='notice') aria-current="page" @endif>{{ __('Notices') }}</a>
@guest
<a href="{{ route('learn.premium') }}">{{ __('Plans') }}</a>
@endguest
@auth
@if(auth()->user()->role==='Admin')<a href="{{ route('adminDashboard') }}">{{ __('Admin dashboard') }}</a>@endif

<details class="account-menu">
<summary @if(request()->routeIs('learn.settings','learn.settings.*','learn.premium','learn.premium.*','learn.payments*','learn.support*','learn.report')) class="account-menu-active" @endif>{{ __('Account') }} <span aria-hidden="true">▾</span></summary>
<div class="account-menu-panel"><span class="account-menu-name">{{ auth()->user()->name }}</span><a href="{{ route('learn.settings') }}" @if(request()->routeIs('learn.settings','learn.settings.*')) aria-current="page" @endif>{{ __('Account settings') }}</a><a href="{{ route('learn.premium') }}" @if(request()->routeIs('learn.premium','learn.premium.*','learn.payments*')) aria-current="page" @endif>{{ __('Premium') }}</a>
<a href="{{ route('learn.support') }}" @if(request()->routeIs('learn.support*','learn.report')) aria-current="page" @endif>{{ __('Help & feedback') }}</a><form action="{{ route('learn.logout') }}" method="post">@csrf<button type="submit">{{ __('Sign out') }}</button></form></div>
</details>
@else
<a class="button small" href="{{ route('learn.login') }}" data-auth-open="signin">{{ __('Sign in') }}</a>
@endauth
<button type="button" class="theme-toggle" data-theme-toggle aria-label="{{ __('Toggle dark mode') }}" title="{{ __('Toggle dark mode') }}"><span aria-hidden="true">◐</span></button>@include('learner.language-switch')</nav></div></header>
<main id="main" class="container">@if(session('success'))<div class="alert" role="status">{{ __(session('success')) }}</div>@endif @if(session('error'))<div class="alert error" role="alert">{{ __(session('error')) }}</div>@endif @if($errors->any())<div class="alert error" role="alert"><strong>{{ __('Please check the following:') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ __($error) }}</li>@endforeach</ul></div>@endif @auth @if(session('mobile_login_id'))<p class="alert"><a href="{{ route('learn.mobile.connect',session('mobile_login_id')) }}">{{ __('Review pending mobile sign-in') }}</a></p>@endif @endauth @yield('content')</main>
<footer class="container footer"><div><strong>Learn to Drive</strong><p>{{ __('Small steps. Safer journeys.') }}</p></div><div>@guest<a href="{{ route('learn.support') }}">{{ __('Help & feedback') }}</a><a href="{{ route('login') }}">{{ __('Admin sign in') }}</a>@endguest</div><p>{{ __('Study resources for Nepal. Check official notices for current exam requirements.') }}</p></footer>@guest @unless(request()->routeIs('learn.login','learn.register')) @include('learner.auth-dialogs') @endunless @endguest
@if(auth()->check() && auth()->user()->hasPremium() && !request()->routeIs('learn.premium.chat','learn.mfa.*'))
@include('learner.chat-widget')
@endif
<script src="{{ asset('js/learner-auth.js') }}" defer></script><script src="{{ asset('js/learner-navigation.js') }}" defer></script></body></html>
