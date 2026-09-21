@extends('admin.layout.master')
@section('title', 'Add Exam Information')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Study Materials</span><h1>Add Exam Information</h1></div></div>
<div class="ltd-form-shell"><form class="ltd-form-card" action="{{ route('insertExamInformation') }}" method="POST" enctype="multipart/form-data" data-add-form>@csrf
    <div class="ltd-form-card__intro"><i class="fas fa-file-alt"></i><div><h2>Upload a exam information</h2><p>Upload one PDF and choose whether it is English or Nepali.</p></div></div>
    <div class="ltd-form-card__body">
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">Document details</h3><p class="text-muted">Write the title and description in the selected PDF language.</p><div class="row"><div class="col-md-12 form-group"><label class="ltd-field-label" for="name">Title <span class="ltd-required">*</span></label><input id="name" name="name" value="{{ old('name') }}" maxlength="255" class="form-control @error('name') is-invalid @enderror" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div><label class="ltd-field-label" for="description">Description <span class="ltd-required">*</span></label><textarea id="description" name="description" rows="4" maxlength="1000" class="form-control @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</section>
        @include('admin.crud.examInformation.file-upload')
    </div>
    <div class="ltd-form-actions"><a href="{{ route('allExamInformation') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit" data-submit-button><i class="fas fa-plus mr-2"></i>Add Exam Information</button></div>
</form></div>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
