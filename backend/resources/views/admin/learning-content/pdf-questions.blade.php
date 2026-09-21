@extends('admin.layout.master')
@section('title', 'PDF Question Review')
@section('content')
<div class="ltd-page-header"><div><h1>PDF Question Review</h1><p>{{ $resource->title }}</p></div><a href="{{ route('learningContent.show', $resource) }}" class="btn btn-outline-secondary">Back to Resource</a></div>
<div class="ltd-panel mb-4">
    <h2 class="ltd-panel__title">Background Extraction</h2>
    <p>Process a page range while you continue reviewing. Progress, cancellation and retries are available on the run page.</p>
    <form method="POST" action="{{ route('pdfRuns.store',$resource) }}" class="row align-items-end">@csrf
        <div class="col-md-3 form-group"><label for="run-mode">Method</label><select id="run-mode" name="mode" class="form-control"><option value="text">English text</option><option value="ocr">Nepali OCR</option></select></div>
        <div class="col-md-3 form-group"><label for="run-from">First PDF page</label><input id="run-from" name="from_page" type="number" min="1" max="1000" value="5" class="form-control" required></div>
        <div class="col-md-3 form-group"><label for="run-to">Last PDF page</label><input id="run-to" name="to_page" type="number" min="1" max="1000" value="6" class="form-control" required></div>
        <div class="col-md-3 form-group"><button class="btn btn-primary" @disabled($resource->status === 'Rejected')>Start Background Run</button></div>
    </form>
    @foreach($runs as $run)<p class="mb-1"><a href="{{ route('pdfRuns.show',$run) }}">Run #{{ $run->id }} · {{ $run->mode }} · pages {{ $run->from_page }}–{{ $run->to_page }} · {{ $run->status }}</a></p>@endforeach
</div>
<div class="ltd-panel mb-4">
    <p>Use text extraction for English banks, or local OCR below for Nepali tables and scanned pages. Diagrams are not extracted.</p>
    <form method="POST" action="{{ route('pdfQuestions.extract', $resource) }}">
        @csrf
        <div class="row align-items-end">
            <div class="col-sm-3 form-group"><label for="from-page">First PDF page</label><input id="from-page" name="from_page" type="number" min="1" max="1000" value="{{ old('from_page', 1) }}" required class="form-control"></div>
            <div class="col-sm-3 form-group"><label for="to-page">Last PDF page</label><input id="to-page" name="to_page" type="number" min="1" max="1000" value="{{ old('to_page', 100) }}" required class="form-control"></div>
            <div class="col-sm-6 form-group"><button class="btn btn-primary" @disabled($resource->status === 'Rejected')>Extract Questions for Review</button></div>
        </div>
        <p class="small text-muted">Up to 100 pages per request; extraction stops at the last page. Repeating a range preserves existing reviews.</p>
    </form>
</div>
<div class="ltd-panel mb-4">
    <h2 class="ltd-panel__title">Nepali PDF OCR</h2>
    <p>Read one PDF page using local OCR. Review every word and choose the correct answers yourself. For the saved Nepali banks, questions begin on PDF page 5.</p>
    <form method="POST" action="{{ route('pdfQuestions.ocr', $resource) }}" class="form-inline" data-ocr-form>
        @csrf<label for="ocr-page" class="mr-2">PDF page</label><input id="ocr-page" name="ocr_page" type="number" min="1" max="1000" value="{{ old('ocr_page', 5) }}" class="form-control mr-2" required>
        <button class="btn btn-primary" @disabled($resource->status === 'Rejected')>Extract Page with OCR</button>
        <span class="small text-muted ml-2" role="status" data-ocr-status>Processing can take up to two minutes.</span>
    </form>
</div>
<div class="ltd-panel"><p>{{ $items->total() }} candidates. Each question must be checked against the PDF before importing.</p>
<p><a class="btn btn-sm btn-outline-warning" href="{{ route('pdfQuestions', ['resource' => $resource, 'needs' => 'answer']) }}">Missing answers: {{ $counts['answer'] }}</a> <a class="btn btn-sm btn-outline-secondary" href="{{ route('pdfQuestions', ['resource' => $resource, 'needs' => 'diagram']) }}">Diagram checks: {{ $counts['diagram'] }}</a></p>
<form method="GET" class="d-flex mb-3" style="gap:.5rem;overflow-x:auto;align-items:center"><label for="candidate-status" class="mb-0">Status</label><select id="candidate-status" name="status" class="form-control" style="width:auto"><option value="">All</option>@foreach(['Pending', 'Imported', 'Rejected'] as $status)<option @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select><label for="candidate-needs" class="mb-0">Review</label><select id="candidate-needs" name="needs" class="form-control" style="width:auto"><option value="">All candidates</option>@foreach(['answer' => 'Missing answer', 'diagram' => 'Diagram check', 'options' => 'Incomplete options', 'ocr' => 'OCR candidates'] as $value => $label)<option value="{{ $value }}" @selected(request('needs') === $value)>{{ $label }}</option>@endforeach</select><button class="btn btn-outline-secondary">Filter</button><a href="{{ route('pdfQuestions', $resource) }}">Reset</a></form>
<div class="table-responsive"><table class="table"><thead><tr><th>PDF page / No.</th><th>Question</th><th>Suggested answer</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($items as $item)
<tr><td>{{ $item->page }} / {{ $item->number }}</td><td>{{ Str::limit($item->payload['question'], 140) }}</td><td>{{ $item->payload['correctOption'] ?: 'Needs review' }}</td><td>{{ $item->status }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('pdfQuestions.show', ['candidate' => $item] + request()->only(['status', 'needs', 'page'])) }}">Review</a></td></tr>
@empty<tr><td colspan="5">No questions extracted yet.</td></tr>@endforelse
</tbody></table></div>{{ $items->links() }}</div>
@endsection
@section('page-script')
<script>document.querySelector('[data-ocr-form]').addEventListener('submit', function () { this.querySelector('button').disabled = true; this.querySelector('[data-ocr-status]').textContent = 'Reading this page locally. Please wait…'; });</script>
@endsection
