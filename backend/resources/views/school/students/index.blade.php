@extends('learner.layout')
@section('title',__('Student exams and licences'))
@section('content')
<h1>{{ __('Student exams and licences') }}</h1>
<p>{{ $school->name }} · <a href="{{ route('learn.school.partner',$school->id) }}">{{ __('My school') }}</a></p>
<form method="get" class="card stack">
<label>{{ __('Search students') }}<input name="q" value="{{ request('q') }}" maxlength="100"></label>
<label>{{ __('Result') }}<select name="result"><option value="">{{ __('All') }}</option>@foreach($results as $result)<option value="{{ $result }}" @selected(request('result')===$result)>{{ __($result) }}</option>@endforeach</select></label>
<label>{{ __('From exam date') }}<input type="date" name="date_from" value="{{ request('date_from') }}"></label>
<label>{{ __('To exam date') }}<input type="date" name="date_to" value="{{ request('date_to') }}"></label>
<button class="button">{{ __('Search') }}</button><a href="{{ route('learn.school.students',$school->id) }}">{{ __('Clear') }}</a>
</form>
<h2>{{ __('School exam schedule') }}</h2>
<p><a href="{{ route('learn.school.students',[$school->id,'date_from'=>now('Asia/Kathmandu')->toDateString(),'result'=>'Scheduled']) }}">{{ __('Upcoming exams') }}</a></p>
@forelse($schedule as $exam)<article class="card"><h3><a href="{{ route('learn.school.students.show',[$school->id,$exam->user_id]) }}">{{ $exam->name }}</a></h3><p>{{ $exam->category }} · {{ __($exam->exam_type) }} · {{ $exam->exam_date }} · {{ __($exam->result) }}</p></article>@empty<p>{{ __('No exam attempts match these filters.') }}</p>@endforelse
{{ $schedule->links('pagination::simple-default') }}
<h2>{{ __('Student records') }}</h2>
<p>{{ __('Choose a student to manage their exam attempts and previous licence categories.') }}</p>
@forelse($students as $membership)
<section class="card"><h2>{{ $membership->user->name }}</h2><p>{{ $membership->user->email }}</p><a class="button" href="{{ route('learn.school.students.show',[$school->id,$membership->user_id]) }}">{{ __('View student records') }}</a></section>
@empty<p>{{ __('No students available for your school role.') }}</p>@endforelse
{{ $students->links('pagination::simple-default') }}
@endsection
