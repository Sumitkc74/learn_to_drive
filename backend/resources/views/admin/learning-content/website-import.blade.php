@extends('admin.layout.master')
@section('title', 'Import from Website')
@section('content')
<div class="ltd-page-header"><div><h1>Import from Website</h1><p class="text-muted">Scan one page, select relevant files, then review them before adding content.</p></div><a class="btn btn-outline-secondary" href="{{ route('learningContent') }}">Back to Learning Content</a></div>
<form class="ltd-panel mb-4" method="POST" action="{{ route('websiteImport.scan') }}">@csrf
    <div class="form-group"><label for="website-url">Website page URL</label><input id="website-url" name="url" type="url" maxlength="2048" value="{{ old('url', $scan['url'] ?? '') }}" placeholder="https://website.org/resources" class="form-control" required></div>
    <div class="form-group"><label for="website-kind">Content category</label><select id="website-kind" name="kind" class="form-control">@foreach(\App\Http\Controllers\Admin\WebsiteImportController::CATEGORIES as $kind => $label)<option value="{{ $kind }}" @selected(old('kind', $scan['kind'] ?? '') === $kind)>{{ $label }}</option>@endforeach</select></div>
    <p class="small text-muted">Use a public HTTPS page. The scanner reads this page only and does not run website scripts or follow redirects. Images may include unrelated graphics; check your selections.</p>
    <button class="btn btn-primary" type="submit">Scan Website</button>
</form>
@if($scan)
<div class="ltd-panel"><h2 class="ltd-panel__title">Discovered resources ({{ count($scan['assets']) }})</h2>
<p>{{ \App\Http\Controllers\Admin\WebsiteImportController::CATEGORIES[$scan['kind']] }} · {{ $scan['url'] }}</p>
@if($scan['assets'])
<p class="text-muted">Select files to add as Pending. Titles come from the page and can be edited during review. Website content is not automatically considered official or verified. Up to 50 results are shown; this scan expires after 30 minutes.</p>
<form method="POST" action="{{ route('websiteImport.store') }}">@csrf<input type="hidden" name="token" value="{{ $scan['token'] }}">
<div class="table-responsive"><table class="table"><thead><tr><th>Select</th><th>Title</th><th>File URL</th></tr></thead><tbody>
@foreach($scan['assets'] as $index => $asset)<tr><td><input type="checkbox" name="selected[]" value="{{ $index }}" aria-label="Select {{ $asset['title'] }}"></td><td style="overflow-wrap:anywhere">{{ $asset['title'] }}</td><td style="overflow-wrap:anywhere">{{ $asset['url'] }}</td></tr>@endforeach
</tbody></table></div><button class="btn btn-success">Add Selected for Review</button>
</form>
@else<p class="text-muted">No matching files found. Try a page with direct PDF links or images. Content loaded by JavaScript is not supported.</p>@endif
</div>
@endif
@endsection
