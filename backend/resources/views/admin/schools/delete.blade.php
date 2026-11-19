@extends('admin.layout.master')
@section('title', __('Delete school'))
@section('content')
<div class="ltd-page-header"><h1>{{ __('Delete school') }}: {{ $school->name }}</h1><a href="{{ route('admin.schools.index') }}">{{ __('Driving schools') }}</a></div>
<section class="ltd-panel">
    <p>{{ __('Deleting a school is permanent. This cannot be undone.') }}</p>
    <p>{{ __('A school with any of the following cannot be deleted: members, vehicles, lessons, bookings, payments, join requests, courses or batches. Remove those first, or suspend the school instead.') }}</p>
    <p>{{ __('School ID') }}: {{ $school->id }} &middot; {{ __('Status') }}: {{ $school->status }}</p>
    @if($school->address)<p>{{ $school->address }}</p>@endif
    <form method="post" action="{{ route('admin.schools.destroy', $school) }}">@csrf @method('DELETE')
        <button type="submit" class="btn btn-danger"><i class="nav-icon fas fa-trash"></i> {{ __('Delete this school permanently') }}</button>
        <a class="btn btn-secondary" href="{{ route('admin.schools.edit', $school) }}">{{ __('Cancel — back to school') }}</a>
    </form>
</section>
@endsection
