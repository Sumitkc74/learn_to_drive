@extends('admin.layout.master')
@section('title', 'Review Learning Resource')
@section('content')
<div class="row"><div class="col-lg-6"><div class="ltd-panel" style="position:sticky;top:1rem">
<h2>Original file</h2>
@if($hasFile)
@if($resource->mime_type === 'application/pdf')<iframe title="Source PDF" src="{{ route('learningContent.file',['resource'=>$resource,'inline'=>1]) }}" style="width:100%;height:75vh;border:0"></iframe>
@else<img src="{{ route('learningContent.file',['resource'=>$resource,'preview'=>1]) }}" alt="Source image" style="max-width:100%;max-height:75vh;object-fit:contain">@endif
<a href="{{ route('learningContent.file',$resource) }}">Download original file</a>
@else<p>Save a private copy to preview it here.</p>@endif
@if($resource->file_hash)
@php($duplicates = \App\Models\LearningContentImport::where('file_hash',$resource->file_hash)->where('id','!=',$resource->id)->limit(10)->get(['id','title']))
@foreach($duplicates as $duplicate)<p class="text-warning">Identical file: <a href="{{ route('learningContent.show',$duplicate->id) }}">#{{ $duplicate->id }} {{ $duplicate->title }}</a></p>@endforeach
@endif
</div></div><div class="col-lg-6">

<div class="ltd-panel mb-3">
@if($resource->parent_resource_id)<p>This is an updated source file. <a href="{{ route('learningContent.show',$resource->parent_resource_id) }}">Compare the earlier approved reference</a>. Approval does not replace published content automatically.</p>@endif
@if($resource->status === 'Approved' && !$resource->parent_resource_id && isset(config('official-content.sources')[$resource->source_key]))
<p>Source check: <strong>{{ $resource->source_check_status ?? 'Not checked yet' }}</strong> {{ $resource->source_checked_at }}</p>
<form method="POST" action="{{ route('learningContent.checkRevision',$resource) }}">@csrf<button class="btn btn-outline-primary">Check for updated source file</button></form>
@foreach(\App\Models\LearningContentImport::where('parent_resource_id',$resource->id)->latest('id')->limit(10)->get() as $revision)<p><a href="{{ route('learningContent.show',$revision) }}">Source revision #{{ $revision->id }}</a> ? {{ $revision->status }}</p>@endforeach
@endif
</div>
@include('admin.learning-content.ai-screening' , ['screened' => $resource, 'screeningKind' => 'learning'])
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Learning Content</span><h1>Review Resource</h1></div><a href="{{ route('learningContent') }}" class="btn btn-outline-secondary">Back to Queue</a></div>
<div class="ltd-panel mb-4" style="overflow-wrap:anywhere">
    <h2 class="ltd-panel__title">{{ $resource->title }}</h2>
    <p>{{ $resource->source_name }} <span class="badge badge-secondary">{{ $resource->status }}</span></p>
    <p class="small text-muted">Added by {{ $resource->creator?->name ?? ($resource->created_by ? 'Former admin' : 'System') }} on {{ $resource->fetched_at->format('M j, Y') }}</p>
    <div class="d-flex flex-wrap mb-3" style="gap:.75rem">
        <a class="btn btn-outline-secondary" href="{{ $resource->source_url }}" target="_blank" rel="noopener noreferrer">Open Source Page</a>
        <a class="btn btn-outline-secondary" href="{{ $resource->asset_url }}" target="_blank" rel="noopener noreferrer">Open Source File</a>
    </div>
    @if($resource->source_key === 'custom-website')<p class="text-muted">Imported from a custom website. Verify the source, content accuracy, and reuse terms before approving.</p>@endif
    @if($resource->kind === 'traffic-sign-sheet')
        <p class="text-muted">A sheet may contain several signs. Confirm its labels and prepare individual sign images before adding Traffic Signs.</p>
    @elseif($resource->kind === 'vision-test-image')
        <p class="text-muted">Review this individual color-vision plate and its reuse terms. Use it for educational practice; a screen image does not certify eyesight or driving fitness.</p>
    @else
        <p class="text-muted">Check the edition, language, licence category, and correct-answer columns. This document is a question collection, not a verified past exam paper.</p>
    @endif
    @if($hasFile)
        @if($resource->kind === 'question-bank-document' && $resource->status !== 'Rejected')
            <a class="btn btn-success" href="{{ route('pdfQuestions', $resource) }}">Extract and Review Questions</a>
        @endif
        @if(in_array($resource->kind, ['traffic-sign-sheet', 'vision-test-image']))
            @if($resource->kind === 'traffic-sign-sheet' && $resource->status !== 'Rejected')<a class="btn btn-success" href="{{ route('learningContent.extract', $resource) }}">Extract Individual Signs</a>@endif
            <figure class="my-3">
                <a href="{{ route('learningContent.file', ['resource' => $resource, 'preview' => 1]) }}" target="_blank" rel="noopener noreferrer" aria-label="Open resource image at full size">
                    <img src="{{ route('learningContent.file', ['resource' => $resource, 'preview' => 1]) }}" alt="{{ $resource->title }}" style="display:block;max-width:100%;height:auto;border:1px solid #dee2e6;border-radius:8px;background:#fff" loading="lazy">
                </a>
                <figcaption class="small text-muted mt-2">Click the image to view it at full size.</figcaption>
            </figure>
        @endif
        <a class="btn btn-primary" href="{{ route('learningContent.file', $resource) }}">Download Private Copy</a>
        <span class="small text-muted ml-2">{{ number_format($resource->file_size / 1024) }} KB · Saved {{ $resource->downloaded_at->format('M j, Y') }}</span>
        <details class="mt-3"><summary>File verification details</summary><p class="small mt-2 mb-0">SHA-256: {{ $resource->file_hash }}</p></details>
    @elseif($resource->status === 'Pending')
        <form method="POST" action="{{ route('learningContent.download', $resource) }}">@csrf<button class="btn btn-primary" type="submit">Save Private Copy for Review</button></form>
        <p class="small text-muted mt-2 mb-0">The saved copy stays in the admin area and uses the upload limits from Application Settings.</p>
    @endif
</div>
@if($hasFile && $resource->kind === 'question-bank-document' && $resource->status !== 'Rejected')
<div class="ltd-panel mb-4">
    <h2 class="ltd-panel__title">Translate PDF</h2>
    @foreach(\App\Models\PdfTranslation::where('learning_content_import_id', $resource->id)->latest()->limit(5)->get() as $translation)
        <p><a href="{{ route('pdfTranslations.show', $translation) }}">Translation #{{ $translation->id }} — {{ $translation->status }}</a></p>
    @endforeach
    @if(app(\App\Services\PdfTextTranslator::class)->ready())
    <form method="POST" action="{{ route('pdfTranslations.start', $resource) }}">@csrf
        <div class="form-group"><label for="source-language">Original PDF language</label><select id="source-language" name="source_language" class="form-control"><option value="en">English → Nepali</option><option value="ne">Nepali → English</option></select></div>
        <p>Processes up to 100 pages in the background. Review every translated page before generating the second PDF.</p>
        <label><input type="checkbox" name="confirmed" value="1" required> Start translation {{ match(config('pdf-translation.driver')) { 'google' => 'using Google Cloud Translation (extracted text is sent to Google and may incur charges)', 'gemini' => 'using Gemini API (extracted text is sent to Google; your API plan and usage limits apply)', default => 'using the configured local model' } }}.</label>
        <button class="btn btn-primary">Start Translation</button>
    </form>
    @else<p class="text-muted mb-0">Automatic translation is not configured. Set up Gemini API, Google Cloud Translation, or a local model first. Your original PDF remains available for review.</p>@endif
</div>
@endif
@if($hasFile && $resource->kind === 'vision-test-image' && $resource->status === 'Approved')
<form class="ltd-panel mb-4" method="POST" action="{{ route('learningContent.storeVision', $resource) }}">
    @csrf
    <input type="hidden" name="file_hash" value="{{ $resource->file_hash }}">
    <h2 class="ltd-panel__title">Add Individual Vision Test</h2>
    <div class="form-group"><label for="vision-number">Test number</label><input id="vision-number" name="testNumber" type="number" min="1" value="{{ old('testNumber') }}" class="form-control" required></div>
    <label><input type="checkbox" name="confirmed" value="1" required> I reviewed this individual plate and its reuse terms.</label>
    <p class="text-muted">Saving makes this image available to learners in Vision Tests. The original colors and source attribution are retained.</p>
    <button class="btn btn-primary" type="submit">Add to Vision Tests</button>
</form>
@endif
@if($hasFile && $resource->kind === 'question-bank-document' && $resource->status === 'Approved')
<form class="ltd-panel mb-4" method="POST" action="{{ route('learningContent.storeExamPaper', $resource) }}">
    @csrf
    <h2 class="ltd-panel__title">Add to Question Banks</h2>
    <p>This PDF will be saved as its own question bank in the <strong>{{ $resource->language }}</strong> language category.</p>
    <input type="hidden" name="file_hash" value="{{ $resource->file_hash }}">
    <div class="form-group"><label for="paper-name">Title ({{ $resource->language }})</label><input id="paper-name" name="name" maxlength="255" value="{{ old('name', $resource->title) }}" class="form-control" required></div>
    <div class="form-group"><label for="paper-description">Description ({{ $resource->language }})</label><textarea id="paper-description" name="description" maxlength="1000" class="form-control" required>{{ old('description') }}</textarea></div>
    <label><input type="checkbox" name="confirmed" value="1" required> I checked the PDF, its language, category, edition, and reuse terms.</label>
    <p class="text-muted">Adding makes the PDF available to learners in Question Banks. Private originals and source details are retained.</p>
    <button class="btn btn-primary">Add to Question Banks</button>
</form>
@endif
@if($resource->status === 'Pending')
@if($resource->kind === 'question-bank-document')
<div class="ltd-panel mb-3"><p class="mb-0">Review and approve this PDF, then use Add to Question Banks. Each PDF becomes a separate bank categorized by language. Individual question extraction remains available separately.</p></div>
@endif
<div class="ltd-settings-grid">
    <form class="ltd-settings-card" action="{{ route('learningContent.review', $resource) }}" method="POST">
        @csrf<input type="hidden" name="decision" value="Approved">
        <div class="ltd-settings-card__heading"><h2>Approve Reference</h2></div>
        <div class="ltd-settings-card__body">
            <div class="form-group"><label for="resource-title">Readable title</label><input id="resource-title" name="title" value="{{ old('title', $resource->title) }}" maxlength="500" class="form-control" required></div>
            <div class="form-group"><label for="resource-language">Language</label><select id="resource-language" name="language" class="form-control" required><option value="">Select language</option>@foreach(['Nepali', 'English', 'Bilingual', 'Not applicable'] as $value)<option @selected(old('language') === $value)>{{ $value }}</option>@endforeach</select></div>
            <div class="form-group"><label for="resource-category">Licence category</label><select id="resource-category" name="licence_category" class="form-control" required><option value="">Select category</option>@foreach(['A/K', 'B', 'All', 'Other', 'Unknown'] as $value)<option @selected(old('licence_category') === $value)>{{ $value }}</option>@endforeach</select></div>
            <div class="form-group"><label for="resource-edition">Edition or publication date</label><input id="resource-edition" name="edition" value="{{ old('edition') }}" maxlength="100" class="form-control" placeholder="Use Unknown if the source does not specify it" required></div>
            <div class="form-group"><label for="approval-notes">Review notes</label><textarea id="approval-notes" name="review_notes" maxlength="3000" rows="3" class="form-control" required>{{ old('decision') === 'Approved' ? old('review_notes') : '' }}</textarea></div>
            <label class="d-flex" style="gap:.5rem"><input type="checkbox" name="confirmed" value="1" required><span>I inspected the saved file and recorded any edition, accuracy, or reuse concerns in my notes.</span></label>
            <p class="small text-muted mb-0">Approval retains a reference for content preparation. It does not publish questions, exam papers, or traffic signs.</p>
        </div>
        <div class="ltd-settings-card__actions"><button class="btn btn-success" type="submit" @disabled(!$hasFile)>Approve Reference</button></div>
    </form>
    <form class="ltd-settings-card" action="{{ route('learningContent.review', $resource) }}" method="POST">
        @csrf<input type="hidden" name="decision" value="Rejected">
        <div class="ltd-settings-card__heading"><h2>Reject Resource</h2></div>
        <div class="ltd-settings-card__body"><label for="rejection-notes">Reason</label><textarea id="rejection-notes" name="review_notes" maxlength="3000" rows="4" class="form-control" required>{{ old('decision') === 'Rejected' ? old('review_notes') : '' }}</textarea><p class="small text-muted mt-2">Rejected resources remain in the queue history and are not re-added by later source checks.</p></div>
        <div class="ltd-settings-card__actions"><button class="btn btn-outline-danger" type="submit">Reject Resource</button></div>
    </form>
</div>
@else
<div class="ltd-panel"><h2 class="ltd-panel__title">Review Record</h2>
    <p>{{ $resource->reviewer?->name ?? 'Former admin' }} · {{ $resource->reviewed_at?->format('M j, Y g:i A') }}</p>
    @if($resource->status === 'Approved')<p>Language: {{ $resource->language }} · Category: {{ $resource->licence_category }} · Edition: {{ $resource->edition }}</p>@endif
    <p style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $resource->review_notes }}</p>
</div>
@endif
</div></div>
@endsection
