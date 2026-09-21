@extends('learner.layout')
@section('title',__('Saved resources'))
@section('content')
<div class="page-heading"><a href="{{ route('learn.account') }}">{{ __('My learning') }}</a><h1>{{ __('Saved resources') }}</h1></div>
<div class="grid">
@forelse($bookmarks as $bookmark)
<article class="card">
@if($item=$resources[$bookmark->id])
@php($info=\App\Http\Controllers\Learner\LibraryController::TYPES[$bookmark->content_type])
<span class="badge">{{ __($info[1]) }}</span>
<h2><a href="{{ route('learn.detail',[$bookmark->content_type,$bookmark->content_id]) }}">{{ \App\Support\LearnerContent::text($item,$info[2]) }}</a></h2>
@else<p>{{ __('This saved resource is no longer available.') }}</p>@endif
<form method="post" action="{{ route('learn.saved.destroy',$bookmark) }}">@csrf @method('DELETE')<button class="button secondary">{{ __('Remove from saved') }}</button></form>
</article>
@empty<p class="empty">{{ __('Open a resource and choose Save resource to keep it here.') }}</p>@endforelse
</div>{{ $bookmarks->links('pagination::simple-default') }}
@endsection
