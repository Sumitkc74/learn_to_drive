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
@if($school && $instructor)
<section class="card"><h2>{{ __('Vehicle') }}</h2><div class="chips">
{{-- Guest-visible chips show the car, not its plate: test-looking registrations read as fake data to visitors. --}}
@forelse($vehicles as $v)
<a class="chip{{ $vehicle && $vehicle->id === $v->id ? ' chip-selected' : '' }}" href="{{ request()->fullUrlWithQuery(['vehicle' => $v->id]) }}">{{ $v->name }} · {{ __($v->transmission) }}</a>
@empty
<p>{{ __('No vehicles for this instructor yet.') }}</p>
@endforelse</div></section>
<section class="card"><h2>{{ __('Day') }}</h2><div class="chips">
@foreach($dayChips as $d)
<a class="chip{{ $day === $d ? ' chip-selected' : '' }}" href="{{ request()->fullUrlWithQuery(['day' => $d]) }}">{{ \Carbon\Carbon::parse($d)->format('D, d M') }}</a>
@endforeach
</div></section>
<section class="card"><h2>{{ __('Time') }}</h2><div class="chips">
@forelse($timeChips as $t)
<a class="chip{{ $time === $t ? ' chip-selected' : '' }}" href="{{ request()->fullUrlWithQuery(['time' => $t]) }}">{{ \Carbon\Carbon::createFromFormat('H:i', $t)->format('g:i A') }}</a>
@empty
<p>{{ __('No times left today.') }}</p>
@endforelse
</div></section>
<section class="card"><h2>{{ __('Duration') }}</h2><div class="chips">
@foreach([30, 60, 90, 120] as $mins)
<a class="chip{{ $duration === $mins ? ' chip-selected' : '' }}" href="{{ request()->fullUrlWithQuery(['duration' => $mins]) }}">{{ $mins }} {{ __('minutes') }}</a>
@endforeach
</div></section>
<section class="card"><h2>{{ __('Meeting point') }}</h2><div class="chips">
@foreach($meetingPresets as $point)
<a class="chip{{ $meetingPoint === $point ? ' chip-selected' : '' }}" href="{{ request()->fullUrlWithQuery(['meeting_point' => $point]) }}">{{ $point }}</a>
@endforeach
</div>
<form method="get" action="{{ route('learn.private-lessons') }}" class="stack">
    @foreach(['school' => $school->id, 'instructor' => $instructor->id, 'vehicle' => $vehicle?->id, 'day' => $day, 'time' => $time, 'duration' => $duration] as $k => $v)
        @if($v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
    @endforeach
    <label>{{ __('Or another point') }}<input type="text" name="meeting_point" maxlength="255"></label>
    <div><button class="button small">{{ __('Use this point') }}</button></div>
</form></section>
<section class="card"><h2>{{ __('Your request') }}</h2>
{{-- The summary is visible to guests browsing the picker, so it shows the car without its plate. --}}
<p><strong>{{ $school->name }} · {{ $instructor->name }}@if($vehicle) · {{ $vehicle->name }}@endif</strong></p>
<p>@if($day){{ \Carbon\Carbon::parse($day)->format('D, d M Y') }}@else — @endif · @if($time){{ \Carbon\Carbon::createFromFormat('H:i', $time)->format('g:i A') }}@else — @endif · {{ $duration }} {{ __('minutes') }} · {{ $meetingPoint ?? '—' }}</p>
@auth
    @if($canSubmit)
    <form method="post" action="{{ route('learn.private-lessons.store') }}" class="stack">@csrf
        <input type="hidden" name="driving_school_id" value="{{ $school->id }}">
        <input type="hidden" name="instructor_id" value="{{ $instructor->id }}">
        <input type="hidden" name="lesson_vehicle_id" value="{{ $vehicle->id }}">
        <input type="hidden" name="starts_at" value="{{ $day }}T{{ $time }}">
        <input type="hidden" name="duration" value="{{ $duration }}">
        <input type="hidden" name="meeting_point" value="{{ $meetingPoint }}">
        <label>{{ __('Note (optional)') }}<input type="text" name="note" maxlength="1000" value="{{ old('note') }}"></label>
        <div><button class="button">{{ __('Request this lesson') }}</button></div>
    </form>
    @else
    <p class="muted">{{ __('Pick a day and a time above to request.') }}</p>
    @endif
@else
    <p><a class="button" href="{{ route('learn.login', ['redirect' => request()->fullUrl()]) }}">{{ __('Sign in to request this lesson') }}</a></p>
@endauth
</section>
@else
<p class="muted">{{ __('Choose a school and instructor above to pick a day and time.') }}</p>
@guest
<p><a class="button" href="{{ route('learn.login', ['redirect' => request()->fullUrl()]) }}">{{ __('Sign in to request this lesson') }}</a></p>
@endguest
@endif
@auth
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
@endauth
@endsection
