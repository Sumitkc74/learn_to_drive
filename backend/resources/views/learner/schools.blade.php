@extends('learner.layout')
@section('title', __('Find driving schools'))
@section('content')
<p class="actions"><a class="button" href="{{ route('learn.lessons') }}">{{ __('Book a private driving lesson') }}</a> <a class="button secondary" href="{{ route('learn.lessons') }}">{{ __('Upcoming classes') }}</a></p>
<div class="page-heading"><p class="eyebrow">Learn to Drive</p><h1>{{ __('Find driving schools') }}</h1><p class="lead">{{ __('Explore approved driving schools by name or area.') }}</p></div>
<form method="get" class="card stack">
    <label for="school-search">{{ __('School name or area') }}</label>
    <input id="school-search" type="search" name="q" value="{{ $search }}" maxlength="100">
    <div><button class="button" type="submit">{{ __('Search') }}</button> <a href="{{ route('learn.schools') }}">{{ __('Clear') }}</a></div>
</form>
<p>{{ trans_choice(':count school found|:count schools found', $schools->total(), ['count' => $schools->total()]) }}</p>
@if($schools->count())
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
<style>.school-marker{background:transparent;border:0}.school-marker span{display:block;width:28px;height:28px;border-radius:50%;background:#ffde17;border:3px solid #292929;box-shadow:0 2px 8px rgba(0,0,0,.35);position:relative}.school-marker span::after{content:"";position:absolute;left:50%;top:50%;width:8px;height:8px;margin:-4px 0 0 -4px;border-radius:50%;background:#292929}.school-logo{max-width:160px;max-height:120px;object-fit:contain;display:block;margin-bottom:12px}.school-logo-fallback{display:grid;place-items:center;width:64px;height:64px;border-radius:12px;background:#ffde17;font-size:28px;font-weight:800;margin-bottom:12px}.school-gallery{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:16px}.school-gallery img,.school-gallery video{width:100%;height:120px;object-fit:cover;border-radius:8px}.school-list{list-style:none;margin:0 0 16px;padding:0;display:grid;gap:8px}.school-list-row{padding:12px 16px}.school-name-button{border:0;background:none;padding:0;font:inherit;font-size:18px;font-weight:700;color:inherit;text-align:left;cursor:pointer}.school-name-button:hover,.school-name-button:focus-visible{text-decoration:underline}.school-dialog{width:min(680px,calc(100% - 28px))}.school-lesson{border-top:1px solid #e2e2e2;padding-top:16px;margin-top:16px}</style>
<p>{{ __('The map shows schools on this page with confirmed coordinates.') }}</p>
<div id="school-map" style="height:420px;max-height:65vh;isolation:isolate;border-radius:16px" role="region" aria-label="{{ __('Driving school map') }}"></div>
<p id="school-map-status" role="status" data-ready="{{ __('Select a marker to see the school name.') }}" data-empty="{{ __('Map locations have not been added for these schools yet.') }}">{{ __('If the map cannot load, use the school list below.') }}</p>
<ul class="school-list">
@foreach($schools as $school)
    <li class="card school-list-row" data-school-marker data-school-id="{{ $school->id }}" @if($school->latitude !== null && $school->longitude !== null) data-lat="{{ $school->latitude }}" data-lng="{{ $school->longitude }}" @endif>
        <button type="button" class="school-name-button" data-open-school-dialog="school-{{ $school->id }}" data-school-name>{{ $school->name }}</button>
    </li>
@endforeach
</ul>
@foreach($schools as $school)
<dialog class="auth-dialog school-dialog" id="school-{{ $school->id }}" aria-labelledby="school-title-{{ $school->id }}">
    <button type="button" class="dialog-close" data-school-close aria-label="{{ __('Close school details') }}">@include('learner.icon',['icon'=>'close'])</button>
    @if($school->logoUrl())<img class="school-logo" src="{{ $school->logoUrl() }}" alt="{{ $school->name }} logo" loading="lazy">@else<span class="school-logo-fallback" aria-hidden="true">{{ mb_strtoupper(mb_substr($school->name, 0, 1)) }}</span>@endif
    <h2 id="school-title-{{ $school->id }}">{{ $school->name }}</h2>
    <p><a class="button" href="{{ route('learn.lessons', ['school' => $school->id]) }}">{{ __('Book a private driving lesson') }}</a></p>
    @auth @if(!in_array($school->id,$memberSchoolIds))<form method="post" action="{{ route('learn.schools.join-request',$school->id) }}">@csrf<button class="button small">{{ __('Request to join') }}</button></form>@endif @endauth
    @if($school->address)<p>{{ $school->address }}</p>@endif
    @if($school->phone)<p>{{ __('Phone') }}: {{ $school->phone }}</p>@endif
    @if($school->email)<p>{{ __('Email') }}: {{ $school->email }}</p>@endif
    @if($school->latitude !== null && $school->longitude !== null)
        <button class="button small" type="button" data-show-school data-school-id="{{ $school->id }}" hidden>{{ __('Show on map') }}</button>
        <a href="https://www.openstreetmap.org/directions?to={{ rawurlencode($school->latitude.','.$school->longitude) }}" target="_blank" rel="noopener noreferrer">{{ __('Get directions') }}</a>
    @else<p class="muted">{{ __('Location not added yet') }}</p>@endif
    @if($school->getMedia(\App\Support\SchoolMedia::GALLERY_COLLECTION)->isNotEmpty())
    <div class="school-gallery">
        @foreach($school->getMedia(\App\Support\SchoolMedia::GALLERY_COLLECTION) as $media)
            @if(\App\Support\SchoolMedia::isVideo($media))
                <video src="{{ $media->getUrl() }}" controls preload="none"></video>
            @else
                <img src="{{ $media->getUrl() }}" alt="" loading="lazy">
            @endif
        @endforeach
    </div>
    @endif
    <h3>{{ __('Upcoming lessons') }}</h3>
    @forelse($upcomingLessons[$school->id] ?? [] as $lesson)
    <section class="school-lesson">
        @include('learner.lessons.details', ['lesson' => $lesson, 'hideLogo' => true])
        @auth
        <details><summary>{{ __('Book this lesson') }}</summary><p>{{ __('Confirm this time and price before booking. You can cancel before the lesson starts.') }}</p>
        <form method="post" action="{{ route('learn.lessons.book', $lesson->id) }}">@csrf<button class="button">{{ __('Confirm booking') }}</button></form></details>
        @else<a class="button" href="{{ route('learn.login') }}">{{ __('Sign in to book') }}</a>@endauth
    </section>
    @empty
    <p class="muted">{{ __('No upcoming lessons from this school yet.') }}</p>
    @endforelse
</dialog>
@endforeach
{{ $schools->links('pagination::simple-default') }}
<script src="{{ asset('vendor/leaflet/leaflet.js') }}" defer></script>
<script src="{{ asset('js/school-dialog.js') }}" defer></script>
<script src="{{ asset('js/school-map.js') }}" defer></script>
@else
<section class="card"><p>{{ __('No approved schools match your search. Try another name or area.') }}</p></section>
@endif
@endsection
