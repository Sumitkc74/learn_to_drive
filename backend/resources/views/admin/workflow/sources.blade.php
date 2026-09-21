@extends('admin.layout.master')
@section('title','Scraping Sources')
@section('content')
<div class="ltd-panel"><h1>Scraping sources</h1><p>Daily times use Asia/Kathmandu. Pausing stops automatic and manual fetches. Existing imports stay available.</p><a href="{{ route('importHistory') }}">View source history and failures</a>
@foreach($sources as $key=>$source)<form class="border rounded p-3 my-3" method="POST" action="{{ route('scrapingSources.update',$key) }}">@csrf<h2 class="h5">{{ $source['name'] }}</h2><label>State <select name="enabled"><option value="1" @selected($source['enabled'])>Enabled</option><option value="0" @selected(!$source['enabled'])>Paused</option></select></label> <label>Daily time <input type="time" name="daily_time" value="{{ $source['daily_time'] }}" required></label> <button class="btn btn-primary">Save</button></form>@endforeach</div>
@endsection
