@extends('learner.layout')
@section('title', __('Private driving lessons'))
@section('content')
<div class="page-heading"><h1>{{ __('Private driving lessons') }}</h1><p>{{ __('Book a car and instructor together for a private lesson. Choose an available time, review the price and confirm your booking.') }}</p></div>
<p>{{ __(config('schools.payments_enabled') ? 'Online payment is available from My driving bookings after confirming your lesson.' : 'Arrange payment directly with the school after booking.') }}</p>
@if($school)<h2>{{ $school->name }}</h2><p><a href="{{ route('learn.lessons') }}">{{ __('View lessons from all schools') }}</a></p>@endif
@auth<p><a href="{{ route('learn.lessons.bookings') }}">{{ __('My driving bookings') }}</a></p>@endauth
<form method="get" class="card stack">
    @if($school)<input type="hidden" name="school" value="{{ $school->id }}">@endif
    <label>{{ __('Instructor, car or school') }}<input name="q" value="{{ request('q') }}" maxlength="100"></label>
    <label>{{ __('Availability') }}<select name="availability"><option value="available">{{ __('Available') }}</option><option value="booked" @selected(request('availability')==='booked')>{{ __('Booked — join waitlist') }}</option></select></label>
    <label>{{ __('Date (Nepal time)') }}<input type="date" name="date" value="{{ request('date') }}"></label>
    <label>{{ __('Transmission') }}<select name="transmission"><option value="">{{ __('All') }}</option>@foreach(['Manual','Automatic'] as $type)<option value="{{ $type }}" @selected(request('transmission') === $type)>{{ __($type) }}</option>@endforeach</select></label>
    <div><button class="button">{{ __('Search') }}</button> <a href="{{ route('learn.lessons', $school ? ['school' => $school->id] : []) }}">{{ __('Clear') }}</a></div>
</form>
<div class="grid">@forelse($lessons as $lesson)
<article class="card">@include('learner.lessons.details')
    @auth
    @if(request('availability')==='booked')
    <p>{{ __('Waitlist alerts do not reserve a slot. Confirm a booking when one becomes available.') }}</p>
    <form method="post" action="{{ route('learn.lessons.waitlist',$lesson->id) }}">@csrf<input type="hidden" name="action" value="{{ $waiting->has($lesson->id)?'leave':'join' }}"><button class="button">{{ $waiting->has($lesson->id)?__('Leave waitlist'):__('Join waitlist') }}</button></form>
    @else
    <details><summary>{{ __('Book this lesson') }}</summary><p>{{ __('Confirm this time and price before booking. You can cancel before the lesson starts.') }}</p>
    <form method="post" action="{{ route('learn.lessons.book', $lesson->id) }}">@csrf<button class="button">{{ __('Confirm booking') }}</button></form></details>
    @endif
    @else<a class="button" href="{{ route('learn.login') }}">{{ __('Sign in to book') }}</a>@endauth
</article>
@empty<section class="card"><p>{{ __('No lesson times are available. Try another date or transmission.') }}</p></section>@endforelse</div>
{{ $lessons->links('pagination::simple-default') }}
@endsection
