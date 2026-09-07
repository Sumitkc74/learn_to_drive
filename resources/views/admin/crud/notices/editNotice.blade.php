@extends('admin.layout.master')
@section('title', 'Edit Notice')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Announcements</span><h1>Edit Notice</h1></div></div>
<div class="ltd-form-shell"><form class="ltd-form-card" action="{{ route('updateNotice', $notice->id) }}" method="POST" data-add-form>@csrf
    <div class="ltd-form-card__intro"><i class="fas fa-edit"></i><div><h2>Update notice</h2><p>Revise its content, publication status, schedule, or expiration.</p></div></div>
    @include('admin.crud.notices.notice-form', ['noticeItem' => $notice])
    <div class="ltd-form-actions"><a href="{{ route('allNotice') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit" data-submit-button><i class="fas fa-save mr-2"></i>Save Changes</button></div>
</form></div>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
