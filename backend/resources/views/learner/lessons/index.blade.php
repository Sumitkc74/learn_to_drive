@extends('learner.layout')
@section('title', __('Upcoming classes'))
@section('content')
<div class="page-heading"><h1>{{ __('Upcoming classes') }}</h1><p>{{ __('Book a course with a car and instructor together. Each course is one driving session: the time shown is when it starts and ends in Nepal time, and the price is for that one session.') }}</p></div>
<p>{{ __(config('schools.payments_enabled') ? 'Online payment is available from My driving bookings after confirming your course.' : 'Arrange payment directly with the school after booking.') }}</p>
@if($school)<h2>{{ $school->name }}</h2><p><a href="{{ route('learn.lessons') }}">{{ __('View courses from all schools') }}</a></p>@endif
@auth<p><a href="{{ route('learn.lessons.bookings') }}">{{ __('My driving bookings') }}</a></p>@endauth
<form method="get" class="filters">
    <label>{{ __('Institute') }}<select name="school"><option value="">{{ __('All institutes') }}</option>@foreach($schools as $s)<option value="{{ $s->id }}" @selected(request('school') == $s->id)>{{ $s->name }}</option>@endforeach</select></label>
    <label>{{ __('Date (Nepal time)') }}<input type="date" name="date" value="{{ request('date') }}"></label>
    <label>{{ __('Car') }}<input name="car" value="{{ request('car') }}" maxlength="100"></label>
    <label>{{ __('Instructor') }}<input name="instructor" value="{{ request('instructor') }}" maxlength="100"></label>
    <label>{{ __('Availability') }}<select name="availability"><option value="available">{{ __('Available') }}</option><option value="booked" @selected(request('availability')==='booked')>{{ __('Booked — join waitlist') }}</option></select></label>
    <label>{{ __('Transmission') }}<select name="transmission"><option value="">{{ __('All') }}</option>@foreach(['Manual','Automatic'] as $type)<option value="{{ $type }}" @selected(request('transmission') === $type)>{{ __($type) }}</option>@endforeach</select></label>
    <button class="button">{{ __('Search') }}</button> <a href="{{ route('learn.lessons', $school ? ['school' => $school->id] : []) }}">{{ __('Clear') }}</a>
</form>
<div class="lesson-list">@forelse($lessons as $lesson)
<article class="card lesson-row" id="lesson-{{ $lesson->id }}">{{-- The public listing shows the car, not its plate: test-looking registrations read as fake data to visitors. --}}@include('learner.lessons.details', ['hideVehicleRegistration' => true])
    <div class="lesson-action">
    @auth
    @if(auth()->user()->id === $lesson->vehicle->instructor_id)
    <p>{{ __('You\'re teaching this course.') }}</p>
    @elseif(request('availability')==='booked')
    <p>{{ __('Waitlist alerts do not reserve a slot. Confirm a booking when one becomes available.') }}</p>
    <form method="post" action="{{ route('learn.lessons.waitlist',$lesson->id) }}">@csrf<input type="hidden" name="action" value="{{ $waiting->has($lesson->id)?'leave':'join' }}"><button class="button">{{ $waiting->has($lesson->id)?__('Leave waitlist'):__('Join waitlist') }}</button></form>
    @else
    <details{{ ($openLessonId ?? null) === $lesson->id ? ' open' : '' }}><summary>{{ __('Book this course') }}</summary><p>{{ __('Confirm this course time and price before booking. The price covers this one session with the listed car and instructor. You can cancel before the course starts.') }}</p>
    <form method="post" action="{{ route('learn.lessons.book', $lesson->id) }}">@csrf<button class="button">{{ __('Confirm course booking') }}</button></form></details>
    @endif
    @else<a class="button" href="{{ route('learn.login', ['redirect' => request()->fullUrlWithQuery(['lesson' => $lesson->id]).'#lesson-'.$lesson->id]) }}">{{ __('Sign in to book this course') }}</a>@endauth
    </div>
</article>
@empty<section class="card"><p>{{ __('No course times are available. Try another date or transmission.') }}</p></section>@endforelse</div>
{{ $lessons->links('pagination::simple-default') }}
@endsection
