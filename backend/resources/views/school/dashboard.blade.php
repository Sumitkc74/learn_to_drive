@extends('learner.layout')
@section('title', $school->name)
@section('content')
<p><a class="button" href="{{ route('learn.school.students',$school->id) }}">{{ __('Student exams and licences') }}</a></p>
<p><a class="button" href="{{ route('learn.school.partner', $school->id) }}">{{ __('Courses, invitations and attendance') }}</a></p>
@if($membershipRole === 'Instructor')<p><a class="button" href="{{ route('learn.instructor.lessons') }}">{{ __('My vehicles and lesson times') }}</a></p>@endif
<div class="page-heading">
    <p class="eyebrow">{{ __('PARTNER SCHOOL') }}</p>
    <h1>{{ $school->name }}</h1>
    <p class="lead">{{ __('Manage enrolled learners and help them prepare with Learn to Drive.') }}</p>
</div>
@if($schools->count() > 1)
<form method="get" class="card stack"><label>{{ __('Choose school') }}<select name="school_id">@foreach($schools as $schoolMembership)<option value="{{ $schoolMembership->driving_school_id }}" @selected($schoolMembership->driving_school_id === $school->id)>{{ $schoolMembership->school->name }}</option>@endforeach</select></label><button class="button">{{ __('Open school') }}</button></form>
@endif
<div class="two-column">
    <section class="card"><span class="badge">{{ __('LEARNERS') }}</span><h2>{{ $studentCount }}</h2><p>{{ __('Learners enrolled in this school.') }}</p></section>
    <section class="card"><span class="badge">{{ __('STAFF') }}</span><h2>{{ $activeStaffCount }}</h2><p>{{ __('Owners, managers, and instructors with access.') }}</p></section>
</div>
@if(in_array($membershipRole, ['Owner', 'Manager'], true))
<section class="card stack">
    <h2>{{ __('Invite member') }}</h2>
    <p class="muted">{{ __('Invite an existing active account. They must accept in their school centre before joining.') }}</p>
    <form method="post" action="{{ route('learn.school.enroll') }}" class="stack">
        @csrf
        <input type="hidden" name="school_id" value="{{ $school->id }}">
        <label>{{ __('Account email') }}<input type="email" name="email" value="{{ old('email') }}" required></label>
        <label>{{ __('School role') }}<select name="role"><option value="Student">{{ __('Student') }}</option><option value="Instructor">{{ __('Instructor') }}</option>@if($membershipRole === 'Owner')<option value="Manager">{{ __('Manager') }}</option>@endif</select></label>
        <button class="button" type="submit">{{ __('Send invitation') }}</button>
    </form>
</section>
@endif
<section class="card">
    <div class="section-heading"><h2>{{ __('Enrolled learners') }}</h2><span class="badge">{{ $studentCount }}</span></div>
    @forelse($students as $membership)
        <article class="notice-row"><strong>{{ $membership->user->name }}</strong><span>{{ $membership->user->email }}</span><span>{{ __('Joined') }} {{ $membership->created_at->format('d M Y') }}</span></article>
        @include('school.remove-member', ['member' => $membership])
    @empty
        <p class="muted">{{ __('No learners are enrolled yet.') }}</p>
    @endforelse
    {{ $students->links('pagination::simple-default') }}
</section>
<section class="card">
    <h2>{{ __('School staff') }}</h2>
    @foreach($staff as $member)
        <article class="notice-row"><strong>{{ $member->user->name }}</strong><span>{{ __($member->role) }}</span><span>{{ $member->user->email }}</span>@unless($member->user->is_active)<span>{{ __('Inactive') }}</span>@endunless</article>
        @include('school.remove-member')
    @endforeach
    {{ $staff->links('pagination::simple-default') }}
</section>
@endsection
