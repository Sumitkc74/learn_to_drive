@extends('admin.layout.master')
@section('title','Backups')
@section('content')
<div class="ltd-panel"><h1>Backups</h1><p>SQLite database and managed uploads are backed up privately and verified using an isolated database copy. Creating a backup can take time; use a quiet period. No live data is restored by these controls.</p>
<p>Backups contain personal data and password hashes. Keep an additional protected copy off this computer. Application code and .env are not included.</p>
<form method="POST" action="{{ route('backups.create') }}">@csrf<button class="btn btn-primary">Create and verify backup</button></form>
<table class="table mt-3"><thead><tr><th>Created</th><th>Status</th><th>Last verified</th><th>Action</th></tr></thead><tbody>@forelse($backups as $backup)<tr><td>{{ $backup['created_at'] }}</td><td>{{ $backup['status'] }}</td><td>{{ $backup['verified_at'] ?? 'Not verified' }}</td><td>@if(in_array($backup['status'],['Created','Verified','Verification failed']))<form method="POST" action="{{ route('backups.verify',$backup['id']) }}">@csrf<button class="btn btn-outline-primary">Verify again</button></form>@endif</td></tr>@empty<tr><td colspan="4">No backups yet.</td></tr>@endforelse</tbody></table></div>
@endsection
