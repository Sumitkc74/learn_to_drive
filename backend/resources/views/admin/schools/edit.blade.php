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
@endsection
