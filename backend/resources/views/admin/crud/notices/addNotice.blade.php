@extends('admin.layout.master')
@section('title', 'Add Notice')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Announcements</span><h1>Add Notice</h1></div></div>
<x-admin.form-card :action="route('insertNotice')" intro-icon="fas fa-bullhorn" intro-title="Create a bilingual notice" intro-text="Prepare content now, publish immediately or schedule it, and optionally remove it automatically." :cancel-url="route('allNotice')" submit-label="Add Notice">
    @include('admin.crud.notices.notice-form', ['noticeItem' => null])
</x-admin.form-card>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
