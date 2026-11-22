@extends('admin.layout.master')
@section('title', 'Add Vision Test')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Learning Content</span><h1>Add Vision Test</h1></div></div>
<x-admin.form-card :action="route('insertVisionTest')" intro-icon="fas fa-eye" intro-title="Create a vision test" intro-text="Use a unique test number and an image that remains clear on mobile screens." :cancel-url="route('allVisionTest')" submit-label="Add Vision Test">
    <div class="ltd-form-card__body">
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">Test details</h3><label class="ltd-field-label" for="testNumber">Test number <span class="ltd-required">*</span></label><input id="testNumber" type="number" name="testNumber" value="{{ old('testNumber') }}" min="1" step="1" class="form-control @error('testNumber') is-invalid @enderror" placeholder="1" required>@error('testNumber')<div class="invalid-feedback">{{ $message }}</div>@enderror<span class="ltd-field-help">Each vision test must have a different number.</span></section>
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">Test image</h3><div class="ltd-file-field"><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" data-image-input="#visionImagePreview" class="form-control-file @error('image') is-invalid @enderror" required>@error('image')<div class="text-danger small mt-2">{{ $message }}</div>@enderror<span class="ltd-field-help">JPEG, PNG or WebP up to 2 MB.</span><img id="visionImagePreview" class="ltd-image-preview" alt="Selected vision test preview"></div></section>
    </div>
</x-admin.form-card>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
