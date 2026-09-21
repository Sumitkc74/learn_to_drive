<div class="row mb-4">
    <div class="col-lg-5 mb-3 mb-lg-0"><div class="ltd-panel h-100"><h2 class="ltd-panel__title">Learning Health</h2>
        <div class="d-flex justify-content-between mb-1"><span>Pass rate</span><strong>{{ $performance['pass_rate'] }}%</strong></div><div class="progress mb-3" style="height:8px"><div class="progress-bar bg-success" style="width:{{ $performance['pass_rate'] }}%"></div></div>
        @php($coverage = $questions['total'] ? (int) round(($questions['published'] / $questions['total']) * 100) : 0)
        <div class="d-flex justify-content-between mb-1"><span>Published question coverage</span><strong>{{ $coverage }}%</strong></div><div class="progress mb-3" style="height:8px"><div class="progress-bar bg-info" style="width:{{ $coverage }}%"></div></div>
        <div class="d-flex justify-content-between"><span class="text-muted">Active learners with attempts</span><strong>{{ $performance['active_learners'] }}</strong></div>
    </div></div>
    <div class="col-lg-7"><div class="ltd-panel h-100"><h2 class="ltd-panel__title">Frequently Missed Questions</h2>
        @if($mostMissed->isEmpty())<p class="text-muted mb-0">More learner attempts are needed before difficult questions can be identified.</p>@else
        <div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>Question</th><th>Category</th><th>Misses</th></tr></thead><tbody>@foreach($mostMissed as $item)<tr><td><a href="{{ route('editQuestion', $item['question']->id) }}">{{ \Illuminate\Support\Str::limit($item['question']->question, 65) }}</a></td><td>{{ $item['question']->category }}</td><td><span class="badge badge-danger">{{ $item['misses'] }}</span></td></tr>@endforeach</tbody></table></div>
        @endif
    </div></div>
</div>
