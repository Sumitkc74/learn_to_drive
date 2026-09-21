@extends('learner.layout')
@section('title',__('Traffic sign flashcards'))
@section('content')
<div class="page-heading"><a href="{{ route('learn.practice') }}">{{ __('← Practice') }}</a><p class="eyebrow">{{ __('LOOK · THINK · REVEAL') }}</p><h1>{{ __('Know the sign?') }}</h1><p class="lead">{{ __('Take a guess before revealing the answer. No timer, no pressure—just a new way to learn.') }}</p></div>
@forelse($signs as $sign)
<article class="card flashcard">
    <span class="badge">{{ __('Card') }} {{ $signs->currentPage() }}</span>
    <img class="flashcard-image" src="{{ $sign->getFirstMedia()->getUrl() }}" alt="{{ __('Traffic sign to identify') }}">
    <details class="flashcard-answer"><summary>{{ __('Reveal the answer') }}</summary><div><h2>{{ \App\Support\LearnerContent::text($sign,'name') }}</h2>@if(app()->getLocale()==='en' && $sign->nepaliSignName)<p lang="ne">{{ $sign->nepaliSignName }}</p>@endif<p class="prose">{{ $sign->description }}</p><a href="{{ route('learn.detail',['traffic-sign',$sign->id]) }}">{{ __('View sign details →') }}</a></div></details>
    <div class="flashcard-navigation">@if($signs->previousPageUrl())<a class="button secondary" href="{{ $signs->previousPageUrl() }}">{{ __('← Previous') }}</a>@endif @if($signs->nextPageUrl())<a class="button" href="{{ $signs->nextPageUrl() }}">{{ __('Next sign →') }}</a>@else<a class="button" href="{{ route('learn.flashcards') }}">{{ __('Start again ↻') }}</a>@endif</div>
    @unless($signs->hasMorePages())<p class="muted">{{ __('You’ve reached the last available card. Revisit any signs you want to remember.') }}</p>@endunless
</article>
@empty<div class="empty"><h2>{{ __('More signs are on the way.') }}</h2><p>{{ __('Flashcards will appear here when traffic sign images are available.') }}</p><a href="{{ route('learn.library','question-bank') }}">{{ __('Explore question banks →') }}</a></div>@endforelse
@endsection
