@extends('admin.layout.master')
@section('title','Extraction Progress')
@section('content')
<div class="ltd-page-header"><h1>Extraction Progress</h1><a class="btn btn-outline-secondary" href="{{ route('pdfQuestions',$run->resource) }}">View Candidates</a></div>
<div class="ltd-panel"><h2>{{ $run->resource->title }}</h2><p>{{ strtoupper($run->mode) }} · PDF pages {{ $run->from_page }}–{{ $run->to_page }}</p>
<h3 id="run-status">{{ $run->status }}</h3><progress id="run-progress" max="{{ $run->to_page - $run->from_page + 1 }}" value="{{ $run->next_page - $run->from_page }}" style="width:100%"></progress>
<p id="run-counts">{{ $run->added }} new candidates; {{ $run->known }} already known.</p><p id="run-error" class="text-danger">{{ $run->error }}</p><p id="run-empty"></p><p id="run-message" role="status">Progress updates automatically. You can leave this page and return later.</p>
<form id="run-cancel" method="POST" action="{{ route('pdfRuns.cancel',$run) }}" @if(!in_array($run->status,['Queued','Running'])) hidden @endif>@csrf<button class="btn btn-outline-danger">Cancel remaining pages</button></form>
<form id="run-retry" method="POST" action="{{ route('pdfRuns.retry',$run) }}" @if($run->status !== 'Failed') hidden @endif>@csrf<button class="btn btn-primary">Retry unfinished page</button></form>
</div>
@endsection
@section('page-script')
<script>
(() => {
    const endpoint = @json(route('pdfRuns.status',$run));
    async function update() {
        try {
            const response = await fetch(endpoint, {headers:{Accept:'application/json'}});
            if (!response.ok) throw new Error();
            const run = await response.json(), done = Math.max(0, Math.min(run.next_page - run.from_page, run.to_page - run.from_page + 1));
            document.getElementById('run-status').textContent = run.cancel_requested && run.status === 'Running' ? 'Cancelling after current page' : run.status;
            const progress = document.getElementById('run-progress'); progress.max = run.to_page - run.from_page + 1; progress.value = done;
            document.getElementById('run-counts').textContent = `${done} of ${progress.max} pages finished; ${run.added} new candidates; ${run.known} already known.`;
            document.getElementById('run-error').textContent = run.error || '';
            document.getElementById('run-empty').textContent = run.empty_pages?.length ? 'No candidates found on pages: ' + run.empty_pages.join(', ') : '';
            const active = ['Queued','Running'].includes(run.status);
            document.getElementById('run-cancel').hidden = !active || run.cancel_requested;
            document.getElementById('run-retry').hidden = run.status !== 'Failed';
            if (active) setTimeout(update,3000);
        } catch { document.getElementById('run-message').textContent = 'Progress could not be refreshed. Reload this page to reconnect.'; }
    }
    update();
})();
</script>
@endsection
