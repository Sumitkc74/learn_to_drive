@extends('admin.layout.master')
@section('title', 'User Details')
@section('content')
<div class="ltd-page-header">
    <div><span class="ltd-page-header__eyebrow">User Management</span><h1>User Details</h1></div>
    <a href="{{ route('allUser') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-2"></i>Back to Users</a>
</div>

<div class="ltd-panel mb-4">
    <div class="row align-items-center">
        <div class="col-md-auto text-center mb-3 mb-md-0"><img src="{{ $user->avatar_url }}" alt="{{ $user->name }} profile photo" width="120" height="120" class="rounded-circle" style="object-fit:cover"></div>
        <div class="col-md">
            <div class="d-flex align-items-center flex-wrap mb-2"><h2 class="mb-0 mr-2">{{ $user->name }}</h2><span class="badge badge-primary">{{ $user->role === 'PremiumUser' ? 'Premium User' : $user->role }}</span></div>
            <p class="mb-2"><i class="fas fa-envelope mr-2 text-muted"></i>{{ $user->email }} <span class="badge badge-{{ $user->hasVerifiedEmail() ? 'success' : 'warning' }}">{{ $user->hasVerifiedEmail() ? 'Verified' : 'Not verified' }}</span></p>
            <p class="mb-2"><i class="fas fa-phone mr-2 text-muted"></i>{{ $user->phoneNumber }} <span class="badge badge-{{ $user->phone_verified_at ? 'success' : 'warning' }}">{{ $user->phone_verified_at ? 'Verified' : 'Not verified' }}</span></p>
            <p class="mb-0 text-muted">Joined {{ $user->created_at->format('M j, Y') }}</p>
        </div>
        <div class="col-md-auto mt-3 mt-md-0"><a href="{{ route('editUser', $user->id) }}" class="btn btn-primary"><i class="fas fa-edit mr-2"></i>Edit Account</a></div>
    </div>
</div>

<div class="row mb-4">
    @foreach([['Attempts', $summary['attempts'], 'clipboard-list'], ['Average score', $summary['average'].'%', 'chart-line'], ['Best score', $summary['best'].'%', 'trophy'], ['Questions answered', $summary['questions'], 'question-circle']] as [$label, $value, $icon])
    <div class="col-sm-6 col-xl-3 mb-3"><div class="ltd-panel h-100"><i class="fas fa-{{ $icon }} text-primary mb-2"></i><div class="h3 mb-1">{{ $value }}</div><div class="text-muted">{{ $label }}</div></div></div>
    @endforeach
</div>

<div class="ltd-panel">
    <div class="d-flex justify-content-between align-items-center mb-3"><h3 class="ltd-panel__title mb-0">Exam and Learning History</h3><span class="text-muted">{{ $attempts->count() }} attempts</span></div>
    @if($attempts->isEmpty())
        <div class="text-center py-5"><i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i><h4>No attempts yet</h4><p class="text-muted mb-0">This user has not completed any recorded practice or exam attempts.</p></div>
    @else
        <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Date</th><th>Questions</th><th>Correct</th><th>Score</th></tr></thead><tbody>
        @foreach($attempts as $attempt)<tr><td>{{ $attempt->created_at->format('M j, Y g:i A') }}</td><td>{{ $attempt->question_count }}</td><td>{{ $attempt->correct_count }}</td><td><span class="badge badge-{{ $attempt->score_percentage >= 80 ? 'success' : ($attempt->score_percentage >= 60 ? 'warning' : 'danger') }}">{{ $attempt->score_percentage }}%</span></td></tr>@endforeach
        </tbody></table></div>
    @endif
</div>
@endsection
