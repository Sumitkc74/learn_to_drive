@extends('learner.layout')
@section('title',__('My learning'))
@section('content')
<div class="page-heading"><p class="eyebrow">{{ __('MY LEARNING') }}</p><h1>{{ __('Hello,') }} {{ auth()->user()->name }}.</h1><p class="lead">{{ __('Keep taking small steps forward.') }}</p></div>
<section class="card next-step" aria-labelledby="next-step-title"><h2 id="next-step-title">{{ __('Recommended next step') }}</h2><p>{{ __($nextStep['reason'],['topic'=>$nextStep['topic'] ?? '']) }}</p><a class="button" href="{{ $nextStep['url'] }}">{{ __($nextStep['label']) }}</a></section>
<p><a href="{{ route('learn.saved') }}">{{ __('Saved resources') }}</a></p>
@include('learner.progress')
<section class="card" id="practice-history"><div class="section-heading"><h2>{{ __('Your practice history') }}</h2><span>{{ $total }} {{ __('completed sessions') }}</span></div>@forelse($attempts as $attempt)<a class="notice-row" href="{{ route('learn.practice.attempt',$attempt) }}"><span>{{ $attempt->completed_at->locale(app()->getLocale())->translatedFormat('d M Y, H:i') }}</span><strong>{{ $attempt->score }} / {{ count($attempt->questions) }} {{ __('correct') }}</strong><span>{{ __('View results →') }}</span></a>@empty<div class="empty">{{ __('Your first session is waiting. Completed practice results will appear here.') }}</div>@endforelse{{ $attempts->links('pagination::simple-default') }}</section>
<p><a href="{{ route('learn.practice.archive') }}">{{ __('Practice archive') }}</a></p>
@endsection
