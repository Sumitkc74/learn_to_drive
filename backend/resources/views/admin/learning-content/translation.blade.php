@extends('admin.layout.master')
@section('title', 'Review PDF Translation')
@section('content')
<div class="ltd-page-header"><h1>PDF Translation #{{ $translation->id }}</h1><a href="{{ route('learningContent.show', $translation->resource) }}" class="btn btn-outline-secondary">Original Reference</a></div>
<div class="ltd-panel mb-3"><p>{{ $translation->source_language === 'en' ? 'English → Nepali' : 'Nepali → English' }} · {{ $translation->status }} · {{ $translation->pages()->count() }} / {{ $translation->total_pages ?? '?' }} pages processed</p>
@if(in_array($translation->status, ['Queued','Running']))<p>Translation runs on the PDF queue worker. Refresh to see progress. No learner content is published.</p><a href="{{ request()->fullUrl() }}" class="btn btn-outline-primary">Refresh Progress</a>@endif
@if($translation->status === 'Failed')<p>{{ $translation->error ?: 'Processing failed. Check the translation provider and OCR setup.' }} Retry resumes at the unfinished page.</p><form method="POST" action="{{ route('pdfTranslations.retry', $translation) }}">@csrf<button class="btn btn-primary">Retry</button></form>@endif
@if($translation->status === 'Completed')<a class="btn btn-success" href="{{ route('learningContent.show', $translation->output_resource_id) }}">Open Reviewed Translation PDF</a>@endif
</div>
@foreach($pages as $page)
<div class="row"><div class="col-lg-6"><div class="ltd-panel mb-3"><h2>Original page {{ $page->page }}</h2><img src="{{ route('pdfTranslations.preview', ['translation' => $translation, 'page' => $page->page]) }}" alt="Original PDF page {{ $page->page }}" style="width:100%"><details><summary>Extracted text</summary><pre style="white-space:pre-wrap">{{ $page->source_text }}</pre></details></div></div>
<div class="col-lg-6"><form class="ltd-panel mb-3" method="POST" action="{{ route('pdfTranslations.page', $translation) }}">@csrf
<h2>Translation — {{ $page->reviewed_at ? 'Reviewed' : 'Needs review' }}</h2>
<input type="hidden" name="page" value="{{ $page->page }}"><input type="hidden" name="version" value="{{ hash('sha256', $page->translated_text) }}">
<label for="translated-text">Translated text</label><textarea id="translated-text" name="translated_text" rows="24" maxlength="40000" class="form-control" required @disabled($translation->status !== 'Review')>{{ old('translated_text', $page->translated_text) }}</textarea>
<label class="mt-3"><input type="checkbox" name="confirmed" value="1" required> I compared the full page, numbering, options, answers, and diagrams with the original.</label>
<button class="btn btn-primary" @disabled($translation->status !== 'Review')>Save Reviewed Page</button>
</form></div></div>
@endforeach
{{ $pages->links() }}
@if($translation->status === 'Review')
<form class="ltd-panel" method="POST" action="{{ route('pdfTranslations.finish', $translation) }}">@csrf
<h2>Generate Reviewed PDF</h2><p>Approve the original reference and review all pages first. The generated PDF includes original page images and reviewed translated text. It stays in Learning Content until you add the pair to Question Banks.</p>
<label><input type="checkbox" name="confirmed" value="1" required> Generate the reviewed translation PDF.</label><button class="btn btn-success">Generate PDF</button>
</form>
@endif
@endsection
