@extends('admin.layout.master')
@section('title','Storage Check')
@section('content')
<div class="ltd-panel"><h1>Storage Check</h1><p>Find missing files, missing owners, and possibly unused uploads. Results are advisory; nothing is deleted.</p>
<form method="POST" action="{{ route('storageCheck.scan') }}">@csrf<button class="btn btn-primary">Run storage check</button> <a href="{{ route('mediaLibrary') }}" class="btn btn-outline-secondary">Media Library</a></form>
@if($report)<p class="mt-3">Checked: {{ $report['checked_at'] }}. Findings: {{ count($report['issues']) }}.</p><div class="table-responsive"><table class="table"><thead><tr><th>Finding</th><th>Disk</th><th>Path</th><th>Reference</th></tr></thead><tbody>@forelse($report['issues'] as $issue)<tr>@foreach(['type','disk','path','record'] as $key)<td style="overflow-wrap:anywhere">{{ $issue[$key] }}</td>@endforeach</tr>@empty<tr><td colspan="4">No issues found in the checked storage.</td></tr>@endforelse</tbody></table></div>@endif
<p class="small text-muted">Checks public media, protected question media and private learning resources. Existing media folders may contain generated variants, which are retained. Backups and temporary processing files are excluded. Confirm usage before removing anything.</p></div>
@endsection
