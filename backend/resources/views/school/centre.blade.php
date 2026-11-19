@extends('learner.layout')
@section('title',__('School centre'))
@section('content')
<h1>{{ __('School centre') }}</h1>
@foreach($schools as $membership)<section class="card"><h2>{{ $membership->school->name }}</h2><p>{{ __($membership->role) }} · {{ __($membership->school->status) }}</p>@if($membership->school->status==='Approved')<a href="{{ route('learn.school.partner',$membership->driving_school_id) }}">{{ __('Open school') }}</a>@endif</section>@endforeach
<h2>{{ __('Invitations') }}</h2>
@forelse($invitations as $invite)<section class="card"><p>{{ $invite->name }} · {{ __($invite->role) }}</p><form method="post" action="{{ route('learn.school.invitation',$invite->id) }}">@csrf<button class="button" name="decision" value="accept">{{ __('Accept invitation') }}</button><button name="decision" value="decline">{{ __('Decline') }}</button></form></section>@empty<p>{{ __('No pending invitations.') }}</p>@endforelse
<h2>{{ __('Notifications') }}</h2>
<form method="post" action="{{ route('learn.school.read') }}">@csrf<button>{{ __('Mark all as read') }}</button></form>
@foreach($notifications as $notice)<p @if(!$notice->read_at) class="alert" @endif>{{ __($notice->message) }} · {{ $notice->created_at }}</p>@endforeach
{{ $notifications->links('pagination::simple-default') }}
<details class="card"><summary>{{ __('Apply as a driving school') }}</summary><p>{{ __('An administrator reviews your school before staff access is enabled.') }}</p><form method="post" action="{{ route('learn.school.apply') }}" enctype="multipart/form-data" class="stack">@csrf
@foreach(['name'=>'School name','address'=>'Address','phone'=>'Phone','email'=>'School email'] as $field=>$label)<label>{{ __($label) }}<input name="{{ $field }}" value="{{ old($field) }}" type="{{ $field==='email'?'email':'text' }}" required></label>@endforeach
<label>{{ __('School logo (optional)') }}<input name="logo" type="file" accept="image/jpeg,image/png,image/webp"></label>
<label>{{ __('Gallery photos (optional)') }}<input name="gallery_images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple></label>
<label>{{ __('Gallery videos (optional)') }}<input name="gallery_videos[]" type="file" accept="video/mp4,video/webm" multiple></label>
<p class="muted">{{ __('Images: JPEG, PNG or WebP, up to 2 MB each. Videos: MP4 or WebM, up to 20 MB each. At most 20 gallery items per school. The logo and gallery show on the public school listing once the school is approved.') }}</p>
<button class="button">{{ __('Submit application') }}</button></form></details>
@endsection
