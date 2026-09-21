@extends('admin.layout.master')
@section('title', 'Search')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Admin Panel</span><h1>Search</h1></div></div>
<div class="ltd-panel mb-3">
    <form method="GET" action="{{ route('adminSearch') }}" class="ltd-global-search-form" role="search">
        <label for="global-search" class="sr-only">Search all management sections</label>
        <input id="global-search" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Search users, content, or a section name" class="form-control">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>
@if($search === '')
    <p class="text-muted">Enter a keyword to search across all eight management sections.</p>
@elseif(count($sections) === 0)
    <p class="text-muted" role="status">No results for “{{ $search }}”. Try a different keyword.</p>
@else
    <p class="text-muted" role="status">Results for “{{ $search }}”</p>
    @foreach($sections as $section)
        <section class="ltd-panel mb-3 ltd-search-results">
            <h2 class="ltd-panel__title"><a href="{{ $section['url'] }}">{{ $section['title'] }}</a> <small>{{ $section['count'] }} matching records</small></h2>
            <ul class="list-unstyled">
                @foreach($section['items'] as $item)
                    <li class="mb-2"><a href="{{ $item['url'] }}">{{ \Illuminate\Support\Str::limit($item['label'], 140) }}</a></li>
                @endforeach
            </ul>
            <a class="btn btn-outline-secondary btn-sm" href="{{ $section['url'] }}">Open {{ $section['title'] }}</a>
        </section>
    @endforeach
@endif
@endsection
