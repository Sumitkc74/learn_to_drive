@extends('admin.layout.master')
@section('title', 'Add Tutorial')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Learning Content</span><h1>Add Tutorial</h1></div></div>
<div class="ltd-form-shell"><form class="ltd-form-card" action="{{ route('insertTutorial') }}" method="POST" enctype="multipart/form-data" data-add-form>@csrf
    <div class="ltd-form-card__intro"><i class="fas fa-play-circle"></i><div><h2>Publish a driving tutorial</h2><p>Add a descriptive title, a valid video URL, and a recognizable thumbnail.</p></div></div>
    <div class="ltd-form-card__body">
        <section class="ltd-form-section"><label class="ltd-field-label" for="title">Tutorial title <span class="ltd-required">*</span></label><input id="title" name="title" value="{{ old('title') }}" maxlength="255" class="form-control @error('title') is-invalid @enderror" placeholder="How to parallel park safely" required>@error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror></section>
        <section class="ltd-form-section"><label class="ltd-field-label" for="description">Description <span class="ltd-required">*</span></label><textarea id="description" name="description" rows="4" maxlength="1000" class="form-control @error('description') is-invalid @enderror" placeholder="Briefly explain what learners will gain from this tutorial." required>{{ old('description') }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror></section>
        <section class="ltd-form-section"><label class="ltd-field-label" for="videoLink">Video URL <span class="ltd-required">*</span></label><input id="videoLink" type="url" name="videoLink" value="{{ old('videoLink') }}" maxlength="2048" class="form-control @error('videoLink') is-invalid @enderror" placeholder="https://www.youtube.com/watch?v=..." required>@error('videoLink')<div class="invalid-feedback">{{ $message }}</div>@enderror<span class="ltd-field-help">Use the full HTTPS link to the tutorial video.</span></section>
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">Thumbnail</h3><div class="ltd-file-field"><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" data-image-input="#tutorialImagePreview" class="form-control-file @error('image') is-invalid @enderror" required>@error('image')<div class="text-danger small mt-2">{{ $message }}</div>@enderror<span class="ltd-field-help">JPEG, PNG or WebP up to 2 MB.</span><img id="tutorialImagePreview" class="ltd-image-preview" alt="Selected tutorial thumbnail"></div></section>
    </div>
    <div class="ltd-form-actions"><a href="{{ route('allTutorial') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit" data-submit-button><i class="fas fa-plus mr-2"></i>Add Tutorial</button></div>
</form></div>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
