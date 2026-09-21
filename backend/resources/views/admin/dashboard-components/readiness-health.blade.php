<section class="ltd-panel mb-4">
<div class="ltd-section-heading"><h2 class="ltd-panel__title">Content Readiness</h2><a href="{{ route('contentReadiness') }}">Review drafts</a></div>
<p>{{ $contentReadiness['drafts'] }} draft questions. Counts can overlap; these checks do not automatically approve content for publication.</p>
<div class="row">@foreach(\App\Services\ContentReadiness::LABELS as $key=>$label)
<div class="col-6 col-xl-3 mb-3"><a class="d-block" href="{{ route('contentReadiness',['issue'=>$key]) }}"><strong style="font-size:1.7rem">{{ number_format($contentReadiness[$key]) }}</strong><br>{{ $label }}</a></div>
@endforeach</div>
<p class="small text-muted mb-0">Image checks cover Road Signs and imported questions flagged for diagrams. Explanations are recommended; neither check changes publishing rules.</p>
</section>
<details class="ltd-panel mb-4" @if(!$systemHealth['worker_recent'] || !$systemHealth['storage_writable'] || $systemHealth['failed'] || $systemHealth['stalled']) open @endif><summary class="ltd-panel__title">System Health <span class="small text-muted">— background processing and storage</span></summary>
<div class="row">
<div class="col-md-6 mb-3"><h3 class="h6">PDF extraction worker</h3><strong class="{{ $systemHealth['worker_recent'] ? 'text-success' : 'text-warning' }}">{{ $systemHealth['worker_status'] }}</strong>
@if($systemHealth['worker_seen_at'])<p class="small">Last seen {{ \Carbon\Carbon::createFromTimestamp($systemHealth['worker_seen_at'])->diffForHumans() }}</p>@else<p class="small text-muted">Worker availability is unknown until it sends a heartbeat.</p>@endif
<p>{{ $systemHealth['queued'] }} queued jobs · {{ $systemHealth['failed'] }} failed runs · {{ $systemHealth['stalled'] }} runs without progress for 5 minutes</p>
<a href="{{ route('learningContent') }}">Open learning-content review</a></div>
<div class="col-md-6 mb-3"><h3 class="h6">Storage</h3>
<p>{{ $systemHealth['storage_writable'] ? 'Private storage is writable' : 'Private storage is not writable' }}</p>
@if($systemHealth['storage_free'] !== null && $systemHealth['storage_total'])
<p>{{ number_format($systemHealth['storage_free']/1073741824,1) }} GB free of {{ number_format($systemHealth['storage_total']/1073741824,1) }} GB on the storage volume.</p>
@else<p>Disk capacity is unavailable.</p>@endif
<p class="small text-muted">Tracked source downloads: {{ number_format($systemHealth['resource_bytes']/1048576,1) }} MB. This excludes previews, OCR tools and logs.</p>
</div></div>
<h3 class="h6">Official source checks</h3><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Source</th><th>Last result</th><th>Checked</th><th>Resources found</th></tr></thead><tbody>
@foreach($systemHealth['sources'] as $source)<tr><td>{{ $source['name'] }}</td><td>{{ $source['status'] }}</td><td>{{ $source['checked_at'] ? \Carbon\Carbon::parse($source['checked_at'])->diffForHumans() : 'Not recorded' }}</td><td>{{ $source['found'] ?? '—' }}</td></tr>@endforeach
</tbody></table></div><p class="small text-muted mb-0">Results are recorded when you check a source. Loading this dashboard does not contact external websites. No earlier check history is assumed.</p>
</details>
