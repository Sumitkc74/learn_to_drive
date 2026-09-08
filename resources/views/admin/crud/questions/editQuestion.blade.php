@extends('admin.layout.master')
@section('title', 'Edit Question')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Question Bank</span><h1>Edit Question</h1></div></div>
<div class="ltd-form-shell">
    <form class="ltd-form-card" action="{{ route('updateQuestion', $edit->id) }}" method="POST" enctype="multipart/form-data" data-add-form>
        @csrf
        <div class="ltd-form-card__intro"><i class="fas fa-edit"></i><div><h2>Update learning content</h2><p>Refine the question, answers, explanation, image, or publication state.</p></div></div>
        @include('admin.crud.questions.question-form', ['questionItem' => $edit])
        <div class="ltd-form-actions"><a href="{{ route('allQuestion') }}" class="btn btn-outline-secondary">Cancel</a><a href="{{ route('previewQuestion', $edit->id) }}" class="btn btn-outline-primary"><i class="fas fa-eye mr-2"></i>Preview</a><button class="btn btn-primary" type="submit" data-submit-button><i class="fas fa-save mr-2"></i>Save Changes</button></div>
    </form>
</div>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
