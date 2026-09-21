@extends('admin.layout.master')
@section('title', 'Add Notice')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Announcements</span><h1>Add Notice</h1></div></div>
<div class="ltd-form-shell"><form class="ltd-form-card" action="{{ route('insertNotice') }}" method="POST" data-add-form>@csrf
    <div class="ltd-form-card__intro"><i class="fas fa-bullhorn"></i><div><h2>Create a bilingual notice</h2><p>Prepare content now, publish immediately or schedule it, and optionally remove it automatically.</p></div></div>
    @include('admin.crud.notices.notice-form', ['noticeItem' => null])
    <div class="ltd-form-actions"><a href="{{ route('allNotice') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit" data-submit-button><i class="fas fa-save mr-2"></i>Save Notice</button></div>
</form></div>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
