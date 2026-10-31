@extends('learner.layout')
@section('title', __('Find driving schools'))
@section('content')
<p><a class="button" href="{{ route('learn.lessons') }}">{{ __('Book a private driving lesson') }}</a></p>
<div class="page-heading"><p class="eyebrow">Learn to Drive</p><h1>{{ __('Find driving schools') }}</h1><p class="lead">{{ __('Explore approved driving schools by name or area.') }}</p></div>
<form method="get" class="card stack">
    <label for="school-search">{{ __('School name or area') }}</label>
    <input id="school-search" type="search" name="q" value="{{ $search }}" maxlength="100">
    <div><button class="button" type="submit">{{ __('Search') }}</button> <a href="{{ route('learn.schools') }}">{{ __('Clear') }}</a></div>
</form>
<p>{{ trans_choice(':count school found|:count schools found', $schools->total(), ['count' => $schools->total()]) }}</p>
@if($schools->count())
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
<p>{{ __('The map shows schools on this page with confirmed coordinates.') }}</p>
<div id="school-map" style="height:420px;max-height:65vh;isolation:isolate;border-radius:16px" role="region" aria-label="{{ __('Driving school map') }}"></div>
<p id="school-map-status" role="status" data-ready="{{ __('Select a marker to see the school name.') }}" data-empty="{{ __('Map locations have not been added for these schools yet.') }}">{{ __('If the map cannot load, use the school list below.') }}</p>
<div class="grid">
@foreach($schools as $school)
    <article class="card" data-school-marker @if($school->latitude !== null && $school->longitude !== null) data-lat="{{ $school->latitude }}" data-lng="{{ $school->longitude }}" @endif>
        <h2 data-school-name>{{ $school->name }}</h2>
        <a class="button" href="{{ route('learn.lessons', ['school' => $school->id]) }}">{{ __('Book a private driving lesson') }}</a>
        @if($school->address)<p>{{ $school->address }}</p>@endif
        @if($school->phone)<p>{{ __('Phone') }}: {{ $school->phone }}</p>@endif
        @if($school->latitude !== null && $school->longitude !== null)
            <button class="button small" type="button" data-show-school hidden>{{ __('Show on map') }}</button>
            <a href="https://www.openstreetmap.org/directions?to={{ rawurlencode($school->latitude.','.$school->longitude) }}" target="_blank" rel="noopener noreferrer">{{ __('Get directions') }}</a>
        @else<p class="muted">{{ __('Location not added yet') }}</p>@endif
    </article>
@endforeach
</div>
{{ $schools->links('pagination::simple-default') }}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous" defer></script>
<script src="{{ asset('js/school-map.js') }}" defer></script>
@else
<section class="card"><p>{{ __('No approved schools match your search. Try another name or area.') }}</p></section>
@endif
@endsection
