@extends('admin.layout.master')
@section('title', 'Add Traffic Sign')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Learning Content</span><h1>Add Traffic Sign</h1></div></div>
<div class="ltd-form-shell"><form class="ltd-form-card" action="{{ route('insertTrafficSign') }}" method="POST" enctype="multipart/form-data" data-add-form>@csrf
    <div class="ltd-form-card__intro"><i class="fas fa-traffic-light"></i><div><h2>Create a traffic-sign entry</h2><p>Provide matching English and Nepali names, a useful explanation, and a clear image.</p></div></div>
    <div class="ltd-form-card__body">
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">Names</h3><div class="row">
            <div class="col-md-6 form-group"><label class="ltd-field-label" for="name">English name <span class="ltd-required">*</span></label><input id="name" name="name" value="{{ old('name') }}" maxlength="255" class="form-control @error('name') is-invalid @enderror" placeholder="No entry" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6 form-group"><label class="ltd-field-label" for="nepaliSignName">Nepali name <span class="ltd-required">*</span></label><input id="nepaliSignName" name="nepaliSignName" value="{{ old('nepaliSignName') }}" maxlength="255" class="form-control @error('nepaliSignName') is-invalid @enderror" lang="ne" required>@error('nepaliSignName')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div></section>
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">Explanation</h3><label class="ltd-field-label" for="description">Description <span class="ltd-required">*</span></label><textarea id="description" name="description" rows="4" maxlength="1000" class="form-control @error('description') is-invalid @enderror" placeholder="Explain what drivers must do when they see this sign." required>{{ old('description') }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</section>
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">Sign image</h3><div class="ltd-file-field"><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" data-image-input="#signImagePreview" class="form-control-file @error('image') is-invalid @enderror" required>@error('image')<div class="text-danger small mt-2">{{ $message }}</div>@enderror<span class="ltd-field-help">Use a square, high-contrast image. Maximum 2 MB.</span><img id="signImagePreview" class="ltd-image-preview" alt="Selected sign preview"></div></section>
    </div>
    <div class="ltd-form-actions"><a href="{{ route('allTrafficSign') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit" data-submit-button><i class="fas fa-plus mr-2"></i>Add Traffic Sign</button></div>
</form></div>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
