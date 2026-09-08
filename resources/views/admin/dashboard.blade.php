@extends('admin.layout.master')

@section('title', 'Dashboard')

@section('content')
    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Dashboard</h1>
        </div>
        <ul class="ltd-breadcrumb">
            <li><a href="/admin">Home</a></li>
            <li>Dashboard</li>
        </ul>
    </div>

    @include('admin.dashboard-components.management')
    @include('admin.dashboard-components.quick-actions')
    @include('admin.dashboard-components.admin-tools')
@endsection
