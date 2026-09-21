@extends('learner.layout')
@section('title',__('Continue from the app'))
@section('content')
<article class="card auth-panel"><h1>{{ __('Continue from the app') }}</h1><p>{{ __('Continue only if this is your account and you opened this page from your own app.') }}</p><p>{{ $user->email }}</p><form method="post" action="{{ route('learn.mobile.accept',$id) }}">@csrf<button class="button">{{ __('Continue') }}</button></form></article>
@endsection
