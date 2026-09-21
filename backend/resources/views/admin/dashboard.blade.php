@extends('admin.layout.master')

@section('title', 'Dashboard')

@section('content')
    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Dashboard</h1>
            <p class="text-muted mb-0">Review incoming content, prepare drafts, and respond to users.</p>
        </div>
        <ul class="ltd-breadcrumb">
            <li><a href="/admin">Home</a></li>
            <li>Dashboard</li>
        </ul>
    </div>

    @include('admin.dashboard-components.import-alerts')
    @include('admin.dashboard-components.start-here')
    @include('admin.dashboard-components.review-workload')
    @include('admin.dashboard-components.readiness-health')
    @include('admin.dashboard-components.recent-activity')
@endsection
