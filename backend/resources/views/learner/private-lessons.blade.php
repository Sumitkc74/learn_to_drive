@extends('learner.layout')
@section('title', __('Book a private driving lesson'))
@section('content')
<div class="page-heading"><h1>{{ __('Book a private driving lesson') }}</h1></div>
<p>{{ __('Choose an instructor and vehicle, then request a time. The instructor sets the price when they accept.') }}</p>
<form method="get" action="{{ route('learn.private-lessons') }}" class="filters">
    <label>{{ __('School') }}<select name="school"><option value="">{{ __('All schools') }}</option>@foreach($schools as $s)<option value="{{ $s->id }}" @selected($school && $school->id === $s->id)>{{ $s->name }}</option>@endforeach</select></label>
    <label>{{ __('Instructor') }}<select name="instructor"><option value="">{{ __('All instructors') }}</option>@foreach($instructors as $u)<option value="{{ $u->id }}" @selected($instructor && $instructor->id === $u->id)>{{ $u->name }}</option>@endforeach</select></label>
    <button class="button">{{ __('Show instructors and vehicles') }}</button>
</form>
<section class="card"><h2>{{ __('Request a private lesson') }}</h2>
<form method="post" action="{{ route('learn.private-lessons.store') }}" class="stack">@csrf
    <label>{{ __('School') }}<select name="driving_school_id" required>@foreach($schools as $s)<option value="{{ $s->id }}" @selected((int) old('driving_school_id', $school?->id) === $s->id)>{{ $s->name }}</option>@endforeach</select></label>
    <label>{{ __('Instructor') }}<select name="instructor_id" required>@foreach($instructors as $u)<option value="{{ $u->id }}" @selected((int) old('instructor_id', $instructor?->id) === $u->id)>{{ $u->name }}</option>@endforeach</select></label>
    <label>{{ __('Vehicle') }}<select name="lesson_vehicle_id" required>@foreach($vehicles as $v)<option value="{{ $v->id }}" @selected((int) old('lesson_vehicle_id') === $v->id)>{{ $v->name }} · {{ __($v->transmission) }} · {{ $v->registration }} · {{ $v->school->name }}</option>@endforeach</select></label>
    <label>{{ __('Start (Nepal time)') }}<input name="starts_at" type="datetime-local" value="{{ old('starts_at') }}" required></label>
    <label>{{ __('Duration in minutes') }}<input name="duration" type="number" min="15" max="180" value="{{ old('duration', 60) }}" required></label>
    <label>{{ __('Meeting point') }}<input name="meeting_point" maxlength="255" value="{{ old('meeting_point') }}" required></label>
    <label>{{ __('Note (optional)') }}<textarea name="note" maxlength="1000">{{ old('note') }}</textarea></label>
    <button class="button">{{ __('Request private lesson') }}</button>
</form></section>
<h2>{{ __('My private lesson requests') }}</h2>
@forelse($myRequests as $req)<article class="card">
    <p><strong>{{ $req->school->name }} · {{ $req->instructor->name }} · {{ $req->vehicle->name }} · {{ $req->vehicle->registration }}</strong></p>
    <p>{{ $req->starts_at->copy()->timezone('Asia/Kathmandu')->format('d M Y H:i') }} · {{ $req->duration_minutes }} {{ __('minutes') }} · {{ $req->meeting_point }}</p>
    @if($req->note)<p>{{ $req->note }}</p>@endif
    <p><strong>{{ __($req->status) }}</strong>@if($req->status === 'Declined' && $req->decline_reason) — {{ $req->decline_reason }}@endif</p>
    @if($req->status === 'Accepted' && $req->lesson)<p><a href="{{ route('learn.lessons.bookings') }}">{{ __('My driving bookings') }}</a></p>@endif
    @if($req->status === 'Pending')<form method="post" action="{{ route('learn.private-lessons.cancel', $req->id) }}">@csrf<button>{{ __('Cancel request') }}</button></form>@endif
</article>@empty<p>{{ __('You have no private lesson requests yet.') }}</p>@endforelse
{{ $myRequests->links('pagination::simple-default') }}
@endsection
