@extends('admin.layout.master')
@section('title', __('Driving schools'))
@section('content')
<div class="ltd-page-header"><h1>{{ __('Driving schools') }}</h1></div>
<p><a href="{{ route('admin.school-payments') }}">{{ __('School payments and refunds') }}</a></p>
<section class="ltd-panel mb-3">
    <form method="get" class="d-flex gap-2 align-items-end">
        <label>{{ __('Status') }}<select name="status" class="form-control"><option value="">{{ __('All') }}</option>@foreach(['Pending','Approved','Suspended'] as $status)<option @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></label>
        <button class="btn btn-primary">{{ __('Filter') }}</button>
    </form>
    <div class="table-responsive"><table class="table"><thead><tr><th>{{ __('School name') }}</th><th>{{ __('Status') }}</th><th>{{ __('Members') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
    @forelse($schools as $school)
        <tr><td>{{ $school->name }}</td><td>{{ __($school->status) }}</td><td>{{ $school->memberships_count }}</td><td><a href="{{ route('admin.schools.edit', $school) }}">{{ __('Manage school') }}</a></td></tr>
    @empty<tr><td colspan="4">{{ __('No schools found.') }}</td></tr>@endforelse
    </tbody></table></div>{{ $schools->links() }}
</section>
<details class="ltd-panel" @if($errors->any()) open @endif><summary>{{ __('Add a school') }}</summary>
    <p>{{ __('Schools start pending. You can assign an existing account as owner now, or leave it blank and add an owner later — then review and approve the school.') }}</p>
    <form method="post" action="{{ route('admin.schools.store') }}">@csrf
        @include('admin.schools.fields', ['school' => null])
        <label for="owner-email" class="form-label">{{ __('Owner account email (optional)') }}</label>
        <input id="owner-email" type="email" name="owner_email" value="{{ old('owner_email') }}" class="form-control mb-3" maxlength="255">
        <button class="btn btn-primary">{{ __('Create school') }}</button>
    </form>
</details>
@endsection
