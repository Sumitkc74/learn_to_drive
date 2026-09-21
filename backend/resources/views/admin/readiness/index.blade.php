@extends('admin.layout.master')
@section('title','Content Readiness')
@section('content')
<div class="ltd-page-header"><h1>Content Readiness</h1><a class="btn btn-outline-secondary" href="{{ route('adminDashboard') }}">Dashboard</a></div>
<div class="ltd-panel mb-3"><form method="GET" class="form-inline"><label for="readiness-issue" class="mr-2">Show</label><select id="readiness-issue" name="issue" class="form-control mr-2"><option value="">All drafts ({{ $counts['drafts'] }})</option>@foreach(\App\Services\ContentReadiness::LABELS as $key=>$label)<option value="{{ $key }}" @selected($issue===$key)>{{ $label }} ({{ $counts[$key] }})</option>@endforeach</select><button class="btn btn-outline-primary">Filter</button></form>
<p class="small text-muted mt-2 mb-0">Review the original source before verifying an answer. Missing images and explanations are review prompts, not automatic publishing decisions.</p></div>
@forelse($questions as $question)
<article class="ltd-panel mb-3"><h2 class="h5">#{{ $question->id }} · {{ $question->question }}</h2>
<div class="row">@foreach(['A'=>'option1','B'=>'option2','C'=>'option3','D'=>'option4'] as $label=>$field)<div class="col-md-6"><p><strong>{{ $label }}.</strong> {{ $question->$field }}</p></div>@endforeach</div>
<p>Selected answer: <strong>{{ $question->correctOption ?: 'Missing' }}</strong> · {{ $question->answer_verified_at ? 'Answer verified' : 'Answer not verified' }}</p>
@if($question->getFirstMediaUrl('question-images'))<img src="{{ $question->getFirstMediaUrl('question-images') }}" alt="Question illustration" style="max-width:240px;max-height:180px" loading="lazy">@endif
<p>{{ $question->explanation ?: 'No explanation added.' }}</p>
<a href="{{ route('editQuestion',$question->id) }}" class="btn btn-outline-primary">Edit Question</a>
@if(!$question->answer_verified_at)<form method="POST" action="{{ route('contentReadiness.verify',$question) }}" class="mt-3">@csrf<input type="hidden" name="review_hash" value="{{ \App\Services\ContentReadiness::answerHash($question) }}"><label class="mr-2"><input type="checkbox" name="confirmed" value="1" required> I checked the wording, four options and correct answer against the source.</label><button class="btn btn-success">Verify Answer</button></form>@endif
</article>
@empty<div class="ltd-panel">No drafts match this readiness check.</div>@endforelse
{{ $questions->links() }}
@endsection
