@extends('admin.layout.master')
@section('title', __('Manage school'))
@section('content')
<div class="ltd-page-header"><h1>{{ $school->name }}</h1><a href="{{ route('admin.schools.index') }}">{{ __('Driving schools') }}</a></div>
<section class="ltd-panel">
    <p>School ID: {{ $school->id }}</p>
    <p>{{ __('Suspending a school immediately blocks its staff dashboard without deleting learner accounts.') }}</p>
    @foreach($school->memberships as $membership)<p>{{ __('Owner') }}: {{ $membership->user->name }} ({{ $membership->user->email }})</p>@endforeach
    <form method="post" action="{{ route('admin.schools.update', $school) }}">@csrf @method('PATCH')
        @include('admin.schools.fields')
        <label for="school-status" class="form-label">{{ __('Status') }}</label>
        <select id="school-status" name="status" class="form-control mb-3">@foreach(['Pending','Approved','Suspended'] as $status)<option value="{{ $status }}" @selected(old('status', $school->status) === $status)>{{ __($status) }}</option>@endforeach</select>
        @if($school->memberships->isEmpty())
            <label for="owner-email" class="form-label">{{ __('Assign owner (optional)') }}</label>
            <input id="owner-email" type="email" name="owner_email" value="{{ old('owner_email') }}" class="form-control mb-3" maxlength="255">
            <p>{{ __('Enter the email of an existing active account to make them the school owner.') }}</p>
        @endif
        <button class="btn btn-primary">{{ __('Save changes') }}</button>
    </form>
</section>
<section class="ltd-panel">
    <h2>{{ __('Platform rates') }}</h2>
    <p>{{ __('Lesson commission') }}: {{ $school->effectiveCommissionPercent() }}% @if($school->commission_percent===null)({{ __('platform default') }})@endif</p>
    <p>{{ __('Enrolment fee') }}: Rs {{ number_format($school->effectiveEnrollmentFeePaisa()/100) }} @if($school->enrollment_fee_paisa===null)({{ __('platform default') }})@endif</p>
    @if($school->proposed_commission_percent!==null || $school->proposed_enrollment_fee_paisa!==null)
        <p>{{ __('Pending proposal') }}: {{ __('Commission') }} {{ $school->proposed_commission_percent!==null ? $school->proposed_commission_percent.'%' : __('no change') }}, {{ __('enrolment fee') }} {{ $school->proposed_enrollment_fee_paisa!==null ? 'Rs '.number_format($school->proposed_enrollment_fee_paisa/100) : __('no change') }}</p>
    @endif
    <form method="post" action="{{ route('admin.schools.propose-rates', $school) }}">@csrf
        <label for="propose-commission" class="form-label">{{ __('Proposed commission (%)') }}</label>
        <input id="propose-commission" type="number" name="commission_percent" value="{{ old('commission_percent') }}" class="form-control mb-3" min="0" max="100">
        <label for="propose-fee" class="form-label">{{ __('Proposed enrolment fee (paisa)') }}</label>
        <input id="propose-fee" type="number" name="enrollment_fee_paisa" value="{{ old('enrollment_fee_paisa') }}" class="form-control mb-3" min="0">
        <p>{{ __('Leave a field blank to leave that rate out of the proposal. The school must accept the proposal before it applies to future lessons and enrolments.') }}</p>
        <button class="btn btn-primary">{{ __('Propose rates') }}</button>
    </form>
</section>
@endsection
