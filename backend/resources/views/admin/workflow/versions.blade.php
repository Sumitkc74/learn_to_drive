@extends('admin.layout.master')
@section('title','Content History')
@section('content')
<div class="ltd-panel"><h1>Content history — {{ $type }} #{{ $record->id }}</h1><a href="{{ route($edit,$record->id) }}">Back to content</a>
<p>History records text and settings from now on. Files are not versioned. Questions and notices restore as drafts; other content updates are visible immediately after confirmation.</p>
@forelse($versions as $version)<details class="border rounded p-3 my-3"><summary>{{ $version->created_at }} — {{ $version->actor ?? 'System / former admin' }} — version #{{ $version->id }}</summary>
<table class="table"><thead><tr><th>Field</th><th>This version</th><th>Current</th></tr></thead><tbody>@foreach(json_decode($version->snapshot,true) as $field=>$value)<tr><td>{{ $field }}</td><td style="white-space:pre-wrap">{{ $value }}</td><td style="white-space:pre-wrap">{{ $record->getRawOriginal($field) }}</td></tr>@endforeach</tbody></table>
<form method="POST" action="{{ route('contentVersions.restore',[$type,$record->id,$version->id]) }}">@csrf<input type="hidden" name="current_hash" value="{{ \App\Support\VersionedContent::hash($record) }}"><label><input type="checkbox" name="confirmed" value="1" required> I reviewed these changes and want to restore this version.</label> <button class="btn btn-outline-primary">Restore version</button></form></details>
@empty<p>No recorded versions yet. The next edit will preserve the previous content.</p>@endforelse{{ $versions->links() }}</div>
@endsection
