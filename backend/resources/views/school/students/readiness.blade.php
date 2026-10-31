@extends('learner.layout')
@section('title',__('Readiness and instructor feedback'))
@section('content')
<h1>{{ $student->name }}</h1><h2>{{ __('Readiness and instructor feedback') }}</h2>
<p>{{ __('This is learning feedback, not an official exam eligibility decision.') }}</p>
<a href="{{ route('learn.school.students.show',[$school->id,$student->id]) }}">{{ __('Student exams and licences') }}</a>
<section class="card"><h2>{{ __('Attendance') }}</h2>@foreach($attendance as $status=>$count)<p>{{ __($status) }}: {{ $count }}</p>@endforeach
<p>{{ __('Completed practices') }}: {{ $practice_count===null?__('Not shared'):$practice_count }}</p></section>
<h2>{{ __('Upcoming exams') }}</h2>@foreach($exams as $exam)<p>{{ $exam->category }} · {{ __($exam->exam_type) }} · {{ $exam->exam_date }} · {{ __($exam->result) }}</p>@endforeach
@if($canEdit && $sessions->isNotEmpty())<details class="card"><summary>{{ __('Record instructor feedback') }}</summary>
<form method="post" action="{{ route('learn.school.feedback',[$school->id,$student->id]) }}" class="stack">@csrf
<label>{{ __('Completed class') }}<select name="session_id">@foreach($sessions as $session)<option value="{{ $session->id }}">{{ $session->batch_name }} · {{ \Carbon\Carbon::parse($session->starts_at)->timezone('Asia/Kathmandu')->format('Y-m-d H:i') }}</option>@endforeach</select></label>
<label>{{ __('Skill practised') }}<input name="skill" required maxlength="100"></label>
<label>{{ __('Assessment') }}<select name="assessment">@foreach($assessments as $value)<option value="{{ $value }}">{{ __($value) }}</option>@endforeach</select></label>
<label>{{ __('Feedback and recommended next lesson') }}<textarea name="notes" required maxlength="2000"></textarea></label>
<button class="button">{{ __('Save') }}</button></form></details>@endif
@forelse($feedback as $entry)<article class="card"><h3>{{ $entry->skill }}</h3><p>{{ __($entry->assessment) }} · {{ $entry->instructor_name }}</p><p style="white-space:pre-wrap">{{ $entry->notes }}</p></article>@empty<p>{{ __('No instructor feedback yet.') }}</p>@endforelse
{{ $feedback->links('pagination::simple-default') }}
@endsection
