@extends('learner.layout')
@section('title', __('Find driving schools'))
@section('content')
<div class="page-heading"><p class="eyebrow">Learn to Drive</p><h1>{{ __('Find driving schools') }}</h1><p class="lead">{{ __('Explore approved driving schools by name or area.') }}</p></div>
<form method="get" class="card stack">
    <label for="school-search">{{ __('School name or area') }}</label>
    <input id="school-search" type="search" name="q" value="{{ $search }}" maxlength="100">
    <div><button class="button" type="submit">{{ __('Search') }}</button> <a href="{{ route('learn.schools') }}">{{ __('Clear') }}</a></div>
</form>
<p>{{ trans_choice(':count school found|:count schools found', $schools->total(), ['count' => $schools->total()]) }}</p>
@if($schools->count())
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
<style>.school-marker{background:transparent;border:0}.school-marker span{display:block;width:28px;height:28px;border-radius:50%;background:#ffde17;border:3px solid #292929;box-shadow:0 2px 8px rgba(0,0,0,.35);position:relative}.school-marker span::after{content:"";position:absolute;left:50%;top:50%;width:8px;height:8px;margin:-4px 0 0 -4px;border-radius:50%;background:#292929}.school-logo{max-width:160px;max-height:120px;object-fit:contain;display:block;margin-bottom:12px}.school-logo-fallback{display:grid;place-items:center;width:64px;height:64px;border-radius:12px;background:#ffde17;font-size:28px;font-weight:800;margin-bottom:12px}.school-gallery{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:16px}.school-gallery img,.school-gallery video{width:100%;height:120px;object-fit:cover;border-radius:8px}.school-list{list-style:none;margin:0 0 16px;padding:0;display:grid;gap:12px}.school-row{display:flex;align-items:center;gap:16px;padding:16px 20px;flex-wrap:wrap}.school-row-logo{width:72px;height:72px;object-fit:contain;flex-shrink:0}.school-row-logo.school-logo-fallback{width:72px;height:72px;margin-bottom:0}.school-row-info{flex:1 1 220px;min-width:0}.school-row-name{font-size:18px;font-weight:700;margin:0 0 4px}.school-row-info p{margin:0 0 2px}.school-row-info p:last-child{margin-bottom:0}.school-row-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}@media (max-width:560px){.school-row-actions{width:100%}}.school-dialog{width:min(680px,calc(100% - 28px))}</style>
<p>{{ __('The map shows schools on this page with confirmed coordinates.') }}</p>
<div id="school-map" style="height:420px;max-height:65vh;isolation:isolate;border-radius:16px" role="region" aria-label="{{ __('Driving school map') }}"></div>
<p id="school-map-status" role="status" data-ready="{{ __('Select a marker to see the school name.') }}" data-empty="{{ __('Map locations have not been added for these schools yet.') }}">{{ __('If the map cannot load, use the school list below.') }}</p>
<ul class="school-list">
@foreach($schools as $school)
    <li class="card school-row" data-school-marker data-school-id="{{ $school->id }}" @if($school->latitude !== null && $school->longitude !== null) data-lat="{{ $school->latitude }}" data-lng="{{ $school->longitude }}" @endif>
        @if($school->logoUrl())<img class="school-row-logo" src="{{ $school->logoUrl() }}" alt="{{ $school->name }} logo" loading="lazy">@else<span class="school-logo-fallback school-row-logo" aria-hidden="true">{{ mb_strtoupper(mb_substr($school->name, 0, 1)) }}</span>@endif
        <div class="school-row-info">
            <h2 class="school-row-name" data-school-name>{{ $school->name }}</h2>
            @if($school->address)<p>{{ $school->address }}</p>@endif
            @if($school->phone)<p>{{ __('Phone') }}: {{ $school->phone }}</p>@endif
        </div>
        <div class="school-row-actions">
            <button type="button" class="button small" data-open-school-dialog="school-{{ $school->id }}">{{ __('View details') }}</button>
            <a class="button small secondary" href="{{ route('learn.lessons', ['school' => $school->id]) }}">{{ __('View available courses') }}</a>
        </div>
    </li>
@endforeach
</ul>
@foreach($schools as $school)
<dialog class="auth-dialog school-dialog" id="school-{{ $school->id }}" aria-labelledby="school-title-{{ $school->id }}">
    <button type="button" class="dialog-close" data-school-close aria-label="{{ __('Close school details') }}">@include('learner.icon',['icon'=>'close'])</button>
    @if($school->logoUrl())<img class="school-logo" src="{{ $school->logoUrl() }}" alt="{{ $school->name }} logo" loading="lazy">@else<span class="school-logo-fallback" aria-hidden="true">{{ mb_strtoupper(mb_substr($school->name, 0, 1)) }}</span>@endif
    <h2 id="school-title-{{ $school->id }}">{{ $school->name }}</h2>
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
    <p><a class="button" href="{{ route('learn.private-lessons', ['school' => $school->id]) }}">{{ __('Request a private session') }}</a> <a class="button secondary" href="{{ route('learn.lessons', ['school' => $school->id]) }}">{{ __('View available courses') }}</a></p>
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
