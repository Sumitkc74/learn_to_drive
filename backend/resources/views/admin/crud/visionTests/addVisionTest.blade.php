@extends('admin.layout.master')
@section('title', 'Add Vision Test')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Learning Content</span><h1>Add Vision Test</h1></div></div>
<div class="ltd-form-shell"><form class="ltd-form-card" action="{{ route('insertVisionTest') }}" method="POST" enctype="multipart/form-data" data-add-form>@csrf
    <div class="ltd-form-card__intro"><i class="fas fa-eye"></i><div><h2>Create a vision test</h2><p>Use a unique test number and an image that remains clear on mobile screens.</p></div></div>
    <div class="ltd-form-card__body">
        <section class="ltd-form-section"><label class="ltd-field-label" for="testNumber">Test number <span class="ltd-required">*</span></label><input id="testNumber" type="number" name="testNumber" value="{{ old('testNumber') }}" min="1" step="1" class="form-control @error('testNumber') is-invalid @enderror" placeholder="1" required>@error('testNumber')<div class="invalid-feedback">{{ $message }}</div>@enderror<span class="ltd-field-help">Each vision test must have a different number.</span></section>
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">Test image</h3><div class="ltd-file-field"><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" data-image-input="#visionImagePreview" class="form-control-file @error('image') is-invalid @enderror" required>@error('image')<div class="text-danger small mt-2">{{ $message }}</div>@enderror<span class="ltd-field-help">JPEG, PNG or WebP up to 2 MB.</span><img id="visionImagePreview" class="ltd-image-preview" alt="Selected vision test preview"></div></section>
    </div>
    <div class="ltd-form-actions"><a href="{{ route('allVisionTest') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit" data-submit-button><i class="fas fa-plus mr-2"></i>Add Vision Test</button></div>
</form></div>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
