@extends('admin.layout.master')
@section('title', 'Preview Question')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Question Bank</span><h1>Learner Preview</h1></div><div><a href="{{ route('editQuestion', $question->id) }}" class="btn btn-outline-primary mr-2"><i class="fas fa-edit mr-1"></i>Edit</a><a href="{{ route('allQuestion') }}" class="btn btn-outline-secondary">Back</a></div></div>

@if($question->status !== 'Published')<div class="alert alert-warning"><i class="fas fa-eye-slash mr-2"></i>This question is <strong>{{ strtolower($question->status) }}</strong> and is not visible to learners.</div>@endif

<div class="row justify-content-center"><div class="col-xl-8"><div class="ltd-panel">
    <div class="d-flex flex-wrap justify-content-between mb-3"><div><span class="badge badge-primary mr-1">{{ $question->category }}</span><span class="badge badge-{{ $question->difficulty === 'Hard' ? 'danger' : ($question->difficulty === 'Easy' ? 'success' : 'warning') }}">{{ $question->difficulty }}</span></div><span class="text-muted small">Question #{{ $question->id }}</span></div>
    @if($question->image_url)<img src="{{ $question->image_url }}" alt="Question illustration" class="img-fluid rounded mb-4 d-block mx-auto" style="max-height:320px;object-fit:contain">@endif
    <h2 class="h4 mb-4">{{ $question->question }}</h2>
    <div class="row">
        @foreach(['A' => $question->option1, 'B' => $question->option2, 'C' => $question->option3, 'D' => $question->option4] as $letter => $option)
        <div class="col-md-6 mb-3"><div class="border rounded p-3 h-100"><strong class="mr-2">{{ $letter }}.</strong>{{ $option }}</div></div>
        @endforeach
    </div>
    <details class="mt-3"><summary class="btn btn-outline-success">Reveal correct answer</summary><div class="alert alert-success mt-3 mb-0"><strong>Correct answer: Option {{ $question->correctOption }}</strong>@if($question->explanation)<hr><p class="mb-0">{{ $question->explanation }}</p>@endif</div></details>
</div></div></div>
@endsection
