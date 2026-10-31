@extends('learner.layout')
@section('title',__('Reschedule lesson'))
@section('content')
<h1>{{ __('Reschedule lesson') }}</h1>
<p>{{ __('Choose an available lesson at the same school and price. Your receipt and payment stay attached to your booking.') }}</p>
<a href="{{ route('learn.lessons.bookings') }}">{{ __('My driving bookings') }}</a>
@forelse($lessons as $lesson)<section class="card">@include('learner.lessons.details')
<form method="post" action="{{ route('learn.lessons.reschedule.save',$booking->id) }}">@csrf<input type="hidden" name="lesson_id" value="{{ $lesson->id }}"><button class="button">{{ __('Confirm new time') }}</button></form></section>
@empty<p>{{ __('No matching times are available. Your original booking is unchanged.') }}</p>@endforelse
{{ $lessons->links('pagination::simple-default') }}
@endsection
