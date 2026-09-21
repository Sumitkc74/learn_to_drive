@extends('admin.layout.master')

@section('title', 'Analytics')

@section('content')
    <div class="ltd-page-header">
        <div><span class="ltd-page-header__eyebrow">Reports &amp; Insights</span><h1>Analytics</h1><p class="text-muted mb-0">User growth, exam performance, and questions learners find difficult.</p></div>
        <ul class="ltd-breadcrumb"><li><a href="{{ route('adminDashboard') }}">Dashboard</a></li><li>Analytics</li></ul>
    </div>

    @include('admin.dashboard-components.stat-boxes')
    <div id="learning-performance">@include('admin.dashboard-components.learning-health')</div>
    <div class="ltd-analytics-chart">
        @include('admin.dashboard-components.signups-chart')
    </div>
@endsection
