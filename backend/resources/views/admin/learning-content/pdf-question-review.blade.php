@extends('admin.layout.master')
@section('title', 'Review Extracted Question')
@section('content')
<div class="ltd-page-header"><div><h1>Review Question {{ $candidate->number }}</h1><p>PDF page {{ $candidate->page }} · {{ $candidate->status }}</p></div><a class="btn btn-outline-secondary" href="{{ route('pdfQuestions', ['resource' => $candidate->resource] + $filters) }}">Back to Candidates</a></div>
<nav class="d-flex mb-3" style="gap:.5rem" aria-label="Question review navigation">
@if($previous)<a class="btn btn-outline-secondary" href="{{ route('pdfQuestions.show', ['candidate' => $previous] + $filters) }}">Previous Question</a>@endif
@if($next)<a class="btn btn-outline-primary" href="{{ route('pdfQuestions.show', ['candidate' => $next] + $filters) }}">Next Question</a>@else<span class="text-muted">Last question in this filtered list.</span>@endif
</nav>
@if($similar)
<div class="ltd-panel mb-3"><h2 class="ltd-panel__title">Possible Duplicates</h2><p>Compare these entries before importing. Similar wording can have a different meaning; similarity does not confirm a duplicate.</p>
@foreach($similar as $match)<p><strong>{{ $match['exact'] ? 'Same normalized wording' : $match['score'].'% word overlap' }}</strong> · {{ $match['type'] === 'question' ? 'Question' : 'Review candidate' }} #{{ $match['id'] }}@if($match['bank']) · bank #{{ $match['bank'] }}@endif<br>{{ $match['question'] }}
@if($match['type'] === 'candidate')<a href="{{ route('pdfQuestions.show',$match['id']) }}" target="_blank" rel="noopener noreferrer">Compare candidate</a>@else<a href="{{ route('allQuestion',['search'=>$match['question']]) }}" target="_blank" rel="noopener noreferrer">Find in Questions</a>@endif</p>@endforeach
</div>
@endif
<div class="ltd-panel mb-4">
    <a class="btn btn-outline-primary" href="{{ route('learningContent.file', $candidate->resource) }}">Download Source PDF</a>
    <p class="mt-2">{{ $candidate->resource->title }} — PDF page {{ $candidate->page }} (the printed page number may differ).</p>
    <ul>@foreach($candidate->warnings as $warning)<li>{{ $warning }}</li>@endforeach</ul>
    <details><summary>Original extracted text</summary><pre style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $candidate->raw_text }}</pre></details>
</div>
<style>
.ltd-pdf-review { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:1.25rem; align-items:start; }
.ltd-pdf-preview { position:sticky; top:1rem; min-width:0; }
.ltd-pdf-preview__viewport { max-height:75vh; overflow:auto; background:#e9ecef; border:1px solid #ced4da; }
.ltd-pdf-preview__viewport img { display:block; width:100%; max-width:none; height:auto; }
@media(max-width:991px) { .ltd-pdf-review { grid-template-columns:minmax(0,1fr); } .ltd-pdf-preview { position:static; } .ltd-pdf-preview__viewport { max-height:60vh; } }
</style>
<div class="ltd-pdf-review">
<aside class="ltd-panel ltd-pdf-preview" aria-label="Source PDF page preview">
    <h2 class="ltd-panel__title">Source PDF</h2>
    <div class="d-flex flex-wrap align-items-center mb-2" style="gap:.5rem">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="pdf-previous" aria-label="Previous PDF page">Previous</button>
        <label for="preview-page" class="mb-0">Page</label><input id="preview-page" type="number" min="1" max="1000" value="{{ $candidate->page }}" class="form-control form-control-sm" style="width:5rem">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="pdf-go">Go</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="pdf-next">Next</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="pdf-original">Question page</button>
    </div>
    <label for="preview-zoom">Zoom</label><input id="preview-zoom" type="range" min="100" max="250" value="100" step="25" class="ml-2" aria-label="Page zoom">
    <a id="preview-open" class="float-right" href="{{ route('pdfQuestions.page', $candidate) }}" target="_blank" rel="noopener noreferrer">Open full size</a>
    <p id="preview-status" class="small text-muted" role="status">Loading the source page…</p>
    <div class="ltd-pdf-preview__viewport"><img id="pdf-page-image" alt="Source PDF page {{ $candidate->page }}" data-src="{{ route('pdfQuestions.page', $candidate) }}"></div>
    <p class="small text-muted mt-2 mb-0">Use Next if the options continue on the following page. If preview is unavailable, use Download Source PDF above.</p>
</aside>
<div>
<form class="ltd-panel" method="POST" action="{{ route('pdfQuestions.review', ['candidate' => $candidate] + $filters) }}">
    @csrf<input type="hidden" name="decision" value="Imported">
    <fieldset @disabled($candidate->status !== 'Pending')>
    @if($candidate->status === 'Pending')@include('admin.crud.questions.translation')@endif
    @foreach(['question' => 'Question', 'option1' => 'Option A', 'option2' => 'Option B', 'option3' => 'Option C', 'option4' => 'Option D'] as $key => $label)
    <div class="form-group"><label for="pdf-{{ $key }}">{{ $label }}</label><textarea id="pdf-{{ $key }}" name="{{ $key }}" rows="{{ $key === 'question' ? 3 : 2 }}" maxlength="{{ $key === 'question' ? 500 : 255 }}" class="form-control" required>{{ old($key, $candidate->payload[$key] ?? '') }}</textarea></div>
    @endforeach
    <div class="row">
    @foreach(['correctOption' => ['Correct answer', ['A', 'B', 'C', 'D']], 'category' => ['Category', ['General', 'Road Signs', 'Traffic Rules', 'Road Safety', 'Vehicle Knowledge']], 'difficulty' => ['Difficulty', ['Easy', 'Medium', 'Hard']]] as $key => [$label, $values])
        <div class="col-md-4 form-group"><label for="pdf-{{ $key }}">{{ $label }}</label><select id="pdf-{{ $key }}" name="{{ $key }}" class="form-control" required><option value="">Select after checking PDF</option>@foreach($values as $value)<option value="{{ $value }}" @selected(old($key, $candidate->payload[$key] ?? '') === $value)>{{ $value }}</option>@endforeach</select></div>
    @endforeach
    </div>
    <div class="form-group"><label for="pdf-explanation">Explanation (optional)</label><textarea id="pdf-explanation" name="explanation" rows="3" maxlength="2000" class="form-control">{{ old('explanation', $candidate->payload['explanation'] ?? '') }}</textarea></div>
    <label><input type="checkbox" name="confirmed" value="1" required> I checked the question, options and correct answer against the PDF.</label>
    <p class="text-muted">Import saves a Draft. Add any required diagram and verify its accuracy before publishing in Questions.</p>
    <button class="btn btn-success">Import as Draft</button>
    <button class="btn btn-primary" name="next" value="1">Save &amp; Next</button>
    </fieldset>
</form>
@if($candidate->status === 'Pending')
<form method="POST" action="{{ route('pdfQuestions.review', ['candidate' => $candidate] + $filters) }}" class="mt-3">@csrf<input type="hidden" name="decision" value="Rejected"><button class="btn btn-outline-danger">Reject Candidate</button> <button class="btn btn-outline-secondary" name="next" value="1">Reject &amp; Next</button></form>
@elseif($candidate->question_id)
<p class="mt-3">Imported as question #{{ $candidate->question_id }}. Manage it in <a href="{{ route('allQuestion') }}">Questions</a>.</p>
@endif
</div></div>
@endsection
@section('page-script')
<script>
(() => {
    const image = document.getElementById('pdf-page-image'), page = document.getElementById('preview-page');
    const status = document.getElementById('preview-status'), original = Number(page.value);
    let displayed = original;
    function show(value) {
        if (!Number.isInteger(value) || value < 1 || value > 1000) { status.textContent = 'Enter a page number between 1 and 1000.'; return; }
        displayed = value; page.value = value;
        const url = new URL(image.dataset.src, window.location.origin); url.searchParams.set('page', value);
        status.textContent = 'Loading PDF page ' + value + '…'; image.hidden = true;
        image.alt = 'Source PDF page ' + value; image.src = url.href;
        document.getElementById('preview-open').href = url.href;
        document.getElementById('pdf-previous').disabled = value === 1;
    }
    image.addEventListener('load', () => { image.hidden = false; status.textContent = 'PDF page ' + displayed; });
    image.addEventListener('error', () => { image.hidden = true; status.textContent = 'Page preview unavailable. Check the page number or download the source PDF.'; });
    document.getElementById('pdf-go').addEventListener('click', () => show(Number(page.value)));
    page.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); show(Number(page.value)); } });
    document.getElementById('pdf-previous').addEventListener('click', () => show(displayed - 1));
    document.getElementById('pdf-next').addEventListener('click', () => show(displayed + 1));
    document.getElementById('pdf-original').addEventListener('click', () => show(original));
    document.getElementById('preview-zoom').addEventListener('input', event => image.style.width = event.target.value + '%');
    show(original);
})();
</script>
@endsection
