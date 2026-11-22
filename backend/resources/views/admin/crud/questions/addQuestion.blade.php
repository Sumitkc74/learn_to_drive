@extends('admin.layout.master')
@section('title', 'Add Question')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Question Bank</span><h1>Add Question</h1></div></div>
<x-admin.form-card :action="route('insertQuestion')" intro-icon="fas fa-question" intro-title="Create a learning question" intro-text="Add distinct answers, learning context, and control when learners can see it." :cancel-url="route('allQuestion')" submit-label="Add Question">
        @include('admin.crud.questions.question-form', ['questionItem' => null])
</x-admin.form-card>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
