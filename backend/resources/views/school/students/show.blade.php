@extends('learner.layout')
@section('title',__('Student exams and licences'))
@section('content')
<h1>{{ $student->name }}</h1><p>{{ $student->email }} · {{ $school->name }}</p>
<p><a href="{{ route('learn.school.students',$school->id) }}">{{ __('Student exams and licences') }}</a></p>
<p>{{ __('These are school-maintained records, not a government licence verification. Notes are visible to the student.') }}</p>
<p><a class="button" href="{{ route('learn.school.readiness',[$school->id,$student->id]) }}">{{ __('Readiness and instructor feedback') }}</a></p>
<h2>{{ __('Exam attempts') }}</h2>
@if($canEdit)
<details class="card" @if(old('form_key')==='exam:new') open @endif><summary>{{ __('Add exam attempt') }}</summary>@include('school.students.form',['kind'=>'exam','record'=>null])</details>
@endif
@forelse($exams as $exam)
<article class="card"><h3>{{ $exam->category }} · {{ __($exam->exam_type) }}</h3>
<p>{{ __('Exam date') }}: {{ $exam->exam_date }} · <strong>{{ __($exam->result) }}</strong></p>
@if($exam->centre)<p>{{ __('Exam centre') }}: {{ $exam->centre }}</p>@endif
@if($exam->result_date)<p>{{ __('Result date') }}: {{ $exam->result_date }}</p>@endif
@if($exam->notes)<p style="white-space:pre-wrap">{{ $exam->notes }}</p>@endif
@if($canEdit)<details @if(old('form_key')==='exam:'.$exam->id) open @endif><summary>{{ __('Edit exam record') }}</summary>@include('school.students.form',['kind'=>'exam','record'=>$exam])</details>@endif
</article>
@empty<p>{{ __('No exam attempts recorded yet.') }}</p>@endforelse
{{ $exams->links('pagination::simple-default') }}
<h2>{{ __('Previous licences') }}</h2>
@if($canEdit)<details class="card" @if(old('form_key')==='licence:new') open @endif><summary>{{ __('Add previous licence category') }}</summary>@include('school.students.form',['kind'=>'licence','record'=>null])</details>@endif
@forelse($licences as $licence)
<article class="card"><h3>{{ __('Licence category') }}: {{ $licence->category }}</h3>
@if($licence->issued_on)<p>{{ __('Issued on') }}: {{ $licence->issued_on }}</p>@endif
@if($licence->expires_on)<p>{{ __('Expires on') }}: {{ $licence->expires_on }} @if($licence->expires_on < now('Asia/Kathmandu')->toDateString()) · {{ __('Expired') }} @endif</p>@endif
@if($licence->notes)<p style="white-space:pre-wrap">{{ $licence->notes }}</p>@endif
@if($canEdit)<details @if(old('form_key')==='licence:'.$licence->id) open @endif><summary>{{ __('Edit licence record') }}</summary>@include('school.students.form',['kind'=>'licence','record'=>$licence])</details>@endif
</article>
@empty<p>{{ __('No previous licence categories recorded. This does not confirm that the student has no licence.') }}</p>@endforelse
@endsection
