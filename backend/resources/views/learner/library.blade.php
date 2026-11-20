@extends('learner.layout')
@section('title',__($title))
@section('content')
<div class="page-heading">@if($type!=='notice')<a href="{{ route('learn.library.home') }}">{{ __('← Learning library') }}</a>@endif<p class="eyebrow">{{ __('THE LEARNING LIBRARY') }}</p><h1>{{ __($title) }}</h1><p class="lead">{{ __($intro) }}</p></div>@include('learner.disclaimer')@if($type==='traffic-sign')@include('learner.sign-source')@endif @if($type==='question-bank')@include('learner.question-source')@endif


<form class="filters" method="get"><label>{{ __('Search') }}<input name="q" value="{{ request('q') }}" maxlength="100" placeholder="{{ __('Search resources') }}"></label>@if(in_array($type,['question-bank','exam-information']))<label>{{ __('Content language') }}<select name="language"><option value="">{{ __('All languages') }}</option>@foreach(['English','Nepali'] as $language)<option value="{{ $language }}" @selected(request('language')===$language)>{{ __($language) }}</option>@endforeach</select></label>@endif<button class="button">{{ __('Search') }}</button><a href="{{ route('learn.library',$type) }}">{{ __('Clear') }}</a></form>
<p class="muted">{{ $items->total() }} {{ __('resources') }}</p><div class="grid">@forelse($items as $item)<a class="card" href="{{ route('learn.detail',[$type,$item->id]) }}">@if(in_array($type,['traffic-sign','vision-test','tutorial']))
<div class="thumbnail">
@if($type==='tutorial')
@if($thumb=$item->thumbnailUrl())
<img src="{{ $thumb }}" alt="{{ \App\Support\LearnerContent::text($item,$field) }}" loading="lazy">
@else
<span>{{ __('No thumbnail') }}</span>
@endif
@elseif($media=$item->getFirstMedia())
<img src="{{ $media->getUrl() }}" alt="{{ $type==='vision-test'?__('Vision practice plate'):\App\Support\LearnerContent::text($item,$field) }}" loading="lazy">
@else
<span>{{ __('Image unavailable') }}</span>
@endif
</div>
@endif<span class="badge">{{ __($item->language ?? $title) }}</span><h3>{{ $type==='vision-test'?__('Vision exercise :number',['number'=>$loop->iteration]):\App\Support\LearnerContent::text($item,$field) }}</h3>@if($type!=='vision-test')<p>{{ \Illuminate\Support\Str::limit(strip_tags(\App\Support\LearnerContent::text($item,'description')),140) }}</p>@endif<span class="text-link">{{ __('Explore →') }}</span></a>@empty
<div class="empty">{{ __('No resources found. Try another search or visit again soon.') }} @if($type==='notice')<a href="{{ config('services.dotm.notices_url', 'https://dotm.gov.np/') }}" target="_blank" rel="noopener">{{ __('See the latest notices on the Department of Transport Management official website') }}</a>@endif</div>
@endforelse</div><div class="pagination">{{ $items->links('pagination::simple-default') }}</div>
@endsection
