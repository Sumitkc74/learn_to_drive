@extends('learner.layout')
@section('title',__('Connect the mobile app'))
@section('content')
<article class="card auth-panel"><h1>{{ __('Connect the mobile app') }}</h1>
<p>{{ __('Only approve if you started this request in your own Learn to Drive app. Check that this code matches the app:') }}</p>
<p class="answer-number">{{ $flow['code'] }}</p><p>{{ auth()->user()->email }}</p>
<form method="post" action="{{ route('learn.mobile.approve',$id) }}">@csrf<button class="button">{{ __('Approve mobile sign-in') }}</button></form></article>
@endsection
