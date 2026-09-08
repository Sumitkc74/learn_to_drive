@extends('admin.layout.master')
@section('title', 'Add Question')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Question Bank</span><h1>Add Question</h1></div></div>
<div class="ltd-form-shell">
    <form class="ltd-form-card" action="{{ route('insertQuestion') }}" method="POST" enctype="multipart/form-data" data-add-form>
        @csrf
        <div class="ltd-form-card__intro"><i class="fas fa-question"></i><div><h2>Create a learning question</h2><p>Add distinct answers, learning context, and control when learners can see it.</p></div></div>
        @include('admin.crud.questions.question-form', ['questionItem' => null])
        <div class="ltd-form-actions"><a href="{{ route('allQuestion') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit" data-submit-button><i class="fas fa-plus mr-2"></i>Add Question</button></div>
    </form>
</div>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
