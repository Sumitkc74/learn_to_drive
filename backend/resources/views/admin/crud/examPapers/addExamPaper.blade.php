@extends('admin.layout.master')
@section('title', 'Add Question Bank')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Study Materials</span><h1>Add Question Bank</h1></div></div>
<x-admin.form-card :action="route('insertExamPaper')" intro-icon="fas fa-file-alt" intro-title="Upload a question bank" intro-text="Upload one PDF and choose whether it is English or Nepali." :cancel-url="route('allExamPaper')" submit-label="Add Question Bank">
    <div class="ltd-form-card__body">
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">Bank details</h3><p class="text-muted">Write the title and description in the selected PDF language.</p><div class="form-group"><label class="ltd-field-label" for="name">Title <span class="ltd-required">*</span></label><input id="name" name="name" value="{{ old('name') }}" maxlength="255" class="form-control @error('name') is-invalid @enderror" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><label class="ltd-field-label" for="description">Description <span class="ltd-required">*</span></label><textarea id="description" name="description" rows="4" maxlength="1000" class="form-control @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</section>
        @include('admin.crud.examPapers.file-upload')
    </div>
</x-admin.form-card>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
