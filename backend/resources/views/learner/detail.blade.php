@extends('learner.layout')
@section('title',$type==='vision-test'?__('Vision practice'):\App\Support\LearnerContent::text($item,$field))
@section('content')
<div class="page-heading"><a href="{{ route('learn.library',$type) }}">← {{ __($title) }}</a><p class="eyebrow">{{ __($item->language ?? $title) }}</p><h1>{{ $type==='vision-test'?__('What number can you see?'):\App\Support\LearnerContent::text($item,$field) }}</h1></div>
@auth
@php($bookmark=\App\Models\LearnerBookmark::where('user_id',auth()->id())->where('content_type',$type)->where('content_id',$item->id)->first())
<form class="bookmark-action" method="post" action="{{ $bookmark?route('learn.saved.destroy',$bookmark):route('learn.saved.store',[$type,$item->id]) }}">@csrf @if($bookmark) @method('DELETE') @endif<button class="button secondary">{{ __($bookmark?'Remove from saved':'Save resource') }}</button></form>
@else<p><a href="{{ route('learn.login') }}">{{ __('Sign in to save resources') }}</a></p>@endauth
<article class="card detail">
@if(in_array($type,['traffic-sign','vision-test'])) @if($media=$item->getFirstMedia())<img class="content-image" src="{{ $media->getUrl() }}" alt="{{ $type==='vision-test'?__('Vision practice plate'):\App\Support\LearnerContent::text($item,$field) }}">@else<p class="empty">{{ __('This image is currently unavailable.') }}</p>@endif @endif
@if(app()->getLocale()==='en' && $type==='traffic-sign' && $item->nepaliSignName)<h2 lang="ne">{{ $item->nepaliSignName }}</h2>@endif
@if($type==='vision-test')<details><summary>{{ __('Reveal the number') }}</summary><p class="answer-number">{{ $item->testNumber }}</p></details><p class="muted">{{ __('This is a learning exercise, not a medical assessment.') }}</p>@endif
<div class="prose">{{ \App\Support\LearnerContent::text($item,'description') }}</div>
@if(in_array($type,['question-bank','exam-information'])) @if($media=$item->getFirstMedia())<a class="button" href="{{ $media->getUrl() }}" target="_blank" rel="noopener">{{ __('Open PDF ↗') }}</a><p class="muted">{{ __('Use your browser’s PDF download button to save a copy.') }}</p>@else<p class="empty">{{ __('The document is currently unavailable.') }}</p>@endif @endif
@if(app()->getLocale()==='en' && $type==='notice' && $item->nepaliDescription)<div class="prose" lang="ne">{{ $item->nepaliDescription }}</div>@endif
@php($external=$type==='tutorial'?$item->videoLink:($type==='notice'?$item->link:null))
@if($external && in_array(strtolower(parse_url($external,PHP_URL_SCHEME) ?? ''),['https','http']))<a class="button" href="{{ $external }}" target="_blank" rel="noopener noreferrer">{{ $type==='tutorial'?__('Watch tutorial'):__('Open notice source') }} ↗</a>@endif
</article><p><a href="{{ route('learn.report',[$type,$item->id]) }}">{{ __('Report a problem with this resource') }}</a></p>
@endsection
