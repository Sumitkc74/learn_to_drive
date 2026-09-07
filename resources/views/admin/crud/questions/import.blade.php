@extends('admin.layout.master')
@section('title', 'Import Questions')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Question Bank</span><h1>Import Questions</h1></div><a href="{{ route('allQuestion') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Back</a></div>
<div class="ltd-form-shell"><form class="ltd-form-card" action="{{ route('questionImport.store') }}" method="POST" enctype="multipart/form-data" data-add-form>@csrf
    <div class="ltd-form-card__intro"><i class="fas fa-file-csv"></i><div><h2>Bulk CSV import</h2><p>Import a prepared question set using the exact template columns.</p></div></div>
    <div class="ltd-form-card__body">
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">1. Download the template</h3><p class="text-muted">Keep the header row unchanged. The template includes one example that you can replace.</p><a href="{{ route('questionImport.template') }}" class="btn btn-outline-primary"><i class="fas fa-download mr-2"></i>Download CSV Template</a></section>
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">2. Prepare and validate</h3><ul class="text-muted mb-0"><li>Use A, B, C, or D for the correct option.</li><li>Use only the categories, difficulties, and statuses shown in the template.</li><li>Save the completed file as UTF-8 CSV.</li><li>If any row is invalid, nothing will be imported.</li></ul></section>
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">3. Upload CSV</h3><label class="ltd-field-label" for="csv_file">Question CSV <span class="ltd-required">*</span></label><input id="csv_file" name="csv_file" type="file" accept=".csv,text/csv" class="form-control-file @error('csv_file') is-invalid @enderror" required><small class="form-text text-muted">Maximum file size: 2 MB.</small>@error('csv_file')<div class="alert alert-danger mt-3 mb-0">@foreach($errors->get('csv_file') as $message)<div>{{ $message }}</div>@endforeach</div>@enderror</section>
    </div><div class="ltd-form-actions"><a href="{{ route('allQuestion') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit" data-submit-button><i class="fas fa-file-import mr-2"></i>Import Questions</button></div>
</form></div>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
