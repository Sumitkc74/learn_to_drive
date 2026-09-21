@extends('admin.layout.master')
@section('title','Import History')
@section('content')
<div class="ltd-panel mb-3"><h1>Import History</h1>
<p>Today's AI screening requests: <strong>{{ $usage->requests ?? 0 }} / {{ config('content-screening.daily_limit') }}</strong>. Reported tokens: {{ number_format($usage->tokens ?? 0) }}.</p>
<p class="text-muted">Usage includes failed provider requests. Tokens are counted when returned by Gemini; this is not a billing estimate. Limits reset at midnight Nepal time.</p>
<p>Daily check times are configured in Scraping Sources (Nepal time). The computer must be awake and signed in.</p>
@foreach($latest as $source)<p class="mb-1"><strong>{{ $source->source }}</strong>: {{ $source->status }} — {{ $source->finished_at ?? $source->started_at }} UTC</p>@endforeach
</div>
<div class="ltd-panel"><form method="GET" class="mb-3"><label for="history-status">Result</label> <select name="status" id="history-status"> <option value="">All</option>@foreach(['Success','Failed','Running'] as $status)<option @selected(request('status')===$status)>{{ $status }}</option>@endforeach</select> <button class="btn btn-sm btn-primary">Filter</button></form>
<div class="table-responsive"><table class="table"><thead><tr><th>Source</th><th>Started (UTC)</th><th>Result</th><th>New</th><th>Known</th><th>Details</th></tr></thead><tbody>
@forelse($runs as $run)<tr><td>{{ $run->source }}</td><td>{{ $run->started_at }}</td><td>{{ $run->status }}</td><td>{{ $run->added }}</td><td>{{ $run->known }}</td><td>{{ $run->error }}</td></tr>@empty<tr><td colspan="6">History will appear after the next source check.</td></tr>@endforelse
</tbody></table></div>{{ $runs->links() }}</div>
@endsection
