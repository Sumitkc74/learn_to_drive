@php
    $latestRuns = \Illuminate\Support\Facades\DB::table('content_source_runs')->whereIn('id', \Illuminate\Support\Facades\DB::table('content_source_runs')->selectRaw('MAX(id)')->groupBy('source'));
    $failedSources = $latestRuns->where('status','Failed')->count();
    $failedAi = \App\Models\LearningContentImport::where('status','Pending')->where('ai_status','Failed')->count() + \App\Models\GovernmentNoticeImport::where('status','Pending')->where('ai_status','Failed')->count();
    $expiring = \App\Models\Notice::visibleToLearners()->whereBetween('expires_at',[now(),now()->addDays(7)])->count();
@endphp
<div class="ltd-panel mb-3"><h3>Import alerts</h3><div class="d-flex flex-wrap" style="gap:1rem">
<a href="{{ route('importHistory') }}">Failed sources: {{ $failedSources }}</a>
<a href="{{ route('learningContent') }}">Sources needing re-review: {{ \App\Models\LearningContentImport::where('source_check_status','Needs review')->count() }}</a>
<a href="{{ route('learningContent') }}">Failed AI checks: {{ $failedAi }} (learning content and notices)</a>
<a href="{{ route('governmentNotices') }}">Review government notices</a>
<a href="{{ route('allNotice',['sort'=>'expires_at','direction'=>'asc','status'=>'Published']) }}">Notices expiring within 7 days: {{ $expiring }}</a>
</div></div>
