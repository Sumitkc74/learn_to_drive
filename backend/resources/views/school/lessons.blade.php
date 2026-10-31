@extends('learner.layout')
@section('title', __('My vehicles and lesson times'))
@section('content')
<div class="page-heading"><h1>{{ __('My vehicles and lesson times') }}</h1><a href="{{ route('learn.school.dashboard') }}">{{ __('My school') }}</a></div>
<p>{{ __('Publish a private lesson with your car, available time, price and meeting point. All entered times are Nepal time. Confirmed bookings appear below.') }}</p>
<section class="card"><h2>{{ __('My vehicles') }}</h2>@foreach($vehicles as $vehicle)<p>{{ $vehicle->name }} · {{ __($vehicle->transmission) }} · {{ $vehicle->registration }} · {{ $vehicle->school->name }}</p>@endforeach
<details @if($errors->has('registration')) open @endif><summary>{{ __('Add vehicle') }}</summary>
<form method="post" action="{{ route('learn.instructor.vehicles') }}" class="stack">@csrf
    <label>{{ __('School') }}<select name="driving_school_id">@foreach($schools as $membership)<option value="{{ $membership->driving_school_id }}">{{ $membership->school->name }}</option>@endforeach</select></label>
    <label>{{ __('Car make and model') }}<input name="name" maxlength="100" value="{{ old('name') }}" required></label>
    <label>{{ __('Vehicle registration (private)') }}<input name="registration" maxlength="40" value="{{ old('registration') }}" required></label>
    <label>{{ __('Transmission') }}<select name="transmission"><option value="Manual">{{ __('Manual') }}</option><option value="Automatic">{{ __('Automatic') }}</option></select></label>
    <button class="button">{{ __('Add vehicle') }}</button>
</form></details></section>
@if($vehicles->isNotEmpty())
<section class="card"><h2>{{ __('Publish a lesson time') }}</h2>
<form method="post" action="{{ route('learn.instructor.lessons.store') }}" class="stack">@csrf
    <label>{{ __('Car') }}<select name="lesson_vehicle_id">@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected(old('lesson_vehicle_id') == $vehicle->id)>{{ $vehicle->name }} · {{ $vehicle->registration }}</option>@endforeach</select></label>
    <label>{{ __('Start (Nepal time)') }}<input name="starts_at" type="datetime-local" value="{{ old('starts_at') }}" required></label>
    <label>{{ __('Duration in minutes') }}<input name="duration" type="number" min="15" max="180" value="{{ old('duration', 60) }}" required></label>
    <label>{{ __('Price (NPR)') }}<input name="price_npr" type="number" min="0" max="100000" value="{{ old('price_npr') }}" required></label>
    <label>{{ __('Meeting point') }}<input name="meeting_point" maxlength="255" value="{{ old('meeting_point') }}" required></label>
    <button class="button">{{ __('Publish lesson') }}</button>
</form></section>
@endif
<h2>{{ __('My lesson schedule') }}</h2>
@forelse($lessons as $lesson)<article class="card">@include('learner.lessons.details')
    @if($lesson->cancelled)<p>{{ __('Cancelled') }}</p>
    @elseif($lesson->bookings->isNotEmpty())@foreach($lesson->bookings as $booking)<p>{{ __('Booked') }}: {{ $booking->learner->name }} · {{ $booking->learner->email }}</p>@endforeach
    @else<p>{{ __('Available') }}</p>@endif
    @if(!$lesson->cancelled && $lesson->starts_at->isFuture())<details><summary>{{ __('Cancel lesson') }}</summary><p>{{ __('This also cancels its booking. Contact the learner if they have booked.') }}</p><form method="post" action="{{ route('learn.instructor.lessons.cancel', $lesson->id) }}">@csrf @method('DELETE')<button class="button">{{ __('Confirm cancellation') }}</button></form></details>@endif
</article>@empty<p>{{ __('No lesson times published yet.') }}</p>@endforelse
{{ $lessons->links('pagination::simple-default') }}
@endsection
