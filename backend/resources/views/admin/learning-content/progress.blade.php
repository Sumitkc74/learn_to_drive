@extends('admin.layout.master')
@section('title', 'Import Progress')
@section('content')
<div class="ltd-page-header"><div><h1>Import Progress</h1><p class="text-muted">PDF extraction and translation jobs. Completed processing still requires content review.</p></div><a href="{{ route('learningContent') }}" class="btn btn-outline-secondary">Learning Content</a></div>
<div class="ltd-panel mb-3"><div class="d-flex flex-wrap" style="gap:1rem">@foreach(['Queued'=>'Queued','Running'=>'Processing','Review'=>'Awaiting review','Generating'=>'Generating PDF','Failed'=>'Failed','Completed'=>'Completed','Cancelled'=>'Cancelled'] as $value=>$label)<a href="{{ route('importProgress',['status'=>$value]) }}">{{ $label }} <strong>{{ $counts[$value] ?? 0 }}</strong></a>@endforeach</div></div>
<div class="ltd-panel">
<form method="GET" class="d-flex flex-wrap align-items-end mb-3" style="gap:.75rem">
<label>Job type<select name="type" class="form-control"><option value="">All types</option>@foreach(['extraction'=>'Question extraction','translation'=>'PDF translation'] as $value=>$label)<option value="{{ $value }}" @selected(request('type')===$value)>{{ $label }}</option>@endforeach</select></label>
<label>Status<select name="status" class="form-control"><option value="">All statuses</option>@foreach(['Queued','Running','Review','Generating','Completed','Failed','Cancelled'] as $value)<option @selected(request('status')===$value)>{{ $value }}</option>@endforeach</select></label>
<button class="btn btn-primary mb-2">Filter</button><a class="btn btn-outline-secondary mb-2" href="{{ route('importProgress') }}">Reset</a><a class="btn btn-outline-primary mb-2" href="{{ request()->fullUrl() }}">Refresh Progress</a>
</form>
<div class="table-responsive"><table class="table"><thead><tr><th>Job / source</th><th>Status</th><th>Progress</th><th>Updated</th><th>Next step</th></tr></thead><tbody>
@forelse($runs as $run)
@php
    $translation = $run->type === 'translation';
    $total = $run->total !== null ? max(0, $run->total - $run->from_page + 1) : null;
    $done = max(0, $run->next_page - $run->from_page);
    if ($total !== null) $done = min($done, $total);
    $stale = in_array($run->status, ['Queued','Running','Generating']) && \Carbon\Carbon::parse($run->updated_at)->lt(now()->subMinutes(5));
@endphp
<tr><td>{{ $translation ? 'PDF translation' : 'Question extraction' }} #{{ $run->id }}<br><a href="{{ route('learningContent.show',$run->learning_content_import_id) }}">{{ $resources[$run->learning_content_import_id] ?? 'Source unavailable' }}</a></td>
<td>{{ $run->status }}@if($stale)<p class="text-warning small">No progress for over 5 minutes. Check the PDF worker before retrying.</p>@endif</td>
<td>{{ $done }} / {{ $total ?? '?' }} pages @if($total)<progress class="d-block" value="{{ $done }}" max="{{ $total }}" aria-label="Pages processed">{{ $done }} / {{ $total }}</progress>@endif</td>
<td>{{ \Carbon\Carbon::parse($run->updated_at)->diffForHumans() }}</td><td>
<a class="btn btn-sm btn-outline-primary" href="{{ route($translation ? 'pdfTranslations.show' : 'pdfRuns.show',$run->id) }}">{{ in_array($run->status,['Review','Completed']) ? 'Open Results' : 'View Details' }}</a>
@if($run->status === 'Failed')<p class="small mt-2">{{ $run->error ?: 'Processing failed. Open details and check the source before retrying.' }}</p><form method="POST" action="{{ route($translation ? 'pdfTranslations.retry' : 'pdfRuns.retry',$run->id) }}">@csrf<button class="btn btn-sm btn-warning">Retry Unfinished Page</button></form>@endif
</td></tr>
@empty<tr><td colspan="5" class="text-center text-muted">No jobs match. Start an extraction or translation from Learning Content.</td></tr>@endforelse
</tbody></table></div>{{ $runs->links() }}
<p class="small text-muted mt-3 mb-0">Queued jobs wait for the PDF worker. Review means the translation is ready for checking. Website scans and direct downloads finish on their own pages.</p>
</div>
@endsection
