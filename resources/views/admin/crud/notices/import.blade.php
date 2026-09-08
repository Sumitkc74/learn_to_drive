@extends('admin.layout.master')
@section('title', 'Import Notices')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Notice Management</span><h1>Import from CSV / Excel</h1></div><a href="{{ route('allNotice') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Back</a></div>
<div class="ltd-form-shell"><form class="ltd-form-card" action="{{ route('noticeImport.store') }}" method="POST" enctype="multipart/form-data" data-add-form>@csrf
    <div class="ltd-form-card__intro"><i class="fas fa-file-excel"></i><div><h2>Bulk notice import</h2><p>Import bilingual notices from a .csv or modern .xlsx workbook.</p></div></div>
    <div class="ltd-form-card__body">
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">1. Download the template</h3><p class="text-muted">Keep the header row unchanged. Replace the example row with your notices.</p><a href="{{ route('noticeImport.template') }}" class="btn btn-outline-primary"><i class="fas fa-download mr-2"></i>Download Notice Template</a></section>
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">2. Prepare the file</h3><ul class="text-muted mb-0"><li>Complete both English and Nepali title and description columns.</li><li>Use Draft, Published, or Archived for status.</li><li>Use dates such as 2026-09-15 09:00, or leave optional dates blank.</li><li>Keep data on the first worksheet and save as UTF-8 CSV or .xlsx.</li><li>If any row is invalid or duplicated, nothing will be imported.</li></ul></section>
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">3. Upload CSV / Excel</h3><label class="ltd-field-label" for="notice_file">Notice file <span class="ltd-required">*</span></label><input id="notice_file" name="notice_file" type="file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="form-control-file @error('notice_file') is-invalid @enderror" required><small class="form-text text-muted">Accepted formats: .csv and .xlsx. Maximum file size: 2 MB.</small>@error('notice_file')<div class="alert alert-danger mt-3 mb-0">@foreach($errors->get('notice_file') as $message)<div>{{ $message }}</div>@endforeach</div>@enderror</section>
    </div><div class="ltd-form-actions"><a href="{{ route('allNotice') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit" data-submit-button><i class="fas fa-file-import mr-2"></i>Import Notices</button></div>
</form></div>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
