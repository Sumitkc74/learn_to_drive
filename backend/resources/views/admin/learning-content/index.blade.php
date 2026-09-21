@extends('admin.layout.master')
@section('title', 'Learning Content')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Official Sources</span><h1>Learning Content</h1><p class="text-muted mb-0">Review question banks, traffic signs, and vision-test images before adding learning content.</p></div></div>
<div class="ltd-panel mb-4">
    <p><a class="btn btn-outline-secondary" href="{{ route('importProgress') }}">Import Progress</a></p>
    <p><a class="btn btn-outline-primary" href="{{ route('websiteImport') }}">Import from Website</a></p>
    <details class="mb-3"><summary>Upload one PDF for translation</summary>
        <form method="POST" action="{{ route('pdfTranslations.upload') }}" enctype="multipart/form-data" class="mt-3">@csrf
            <div class="form-group"><label for="upload-pdf-title">Title</label><input id="upload-pdf-title" name="title" maxlength="255" class="form-control" required></div>
            <div class="form-group"><label for="upload-pdf-file">English or Nepali PDF</label><input id="upload-pdf-file" name="pdf" type="file" accept="application/pdf" class="form-control" required></div>
            <button class="btn btn-outline-primary">Save PDF for Review</button>
        </form>
    </details>
    <form action="{{ route('learningContent') }}" method="GET" class="d-flex align-items-end flex-wrap mb-3" style="gap:1rem">
        <div class="flex-grow-1"><label for="content-category">Content category</label><select id="content-category" name="kind" class="form-control"><option value="">All content</option>@foreach($categories as $value => $label)<option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>@endforeach</select></div>
        <button type="submit" class="btn btn-outline-primary">Show Category</button>
    </form>
    <form action="{{ route('learningContent.fetch') }}" method="POST" class="d-flex align-items-end flex-wrap" style="gap:1rem">
        @csrf
        <div class="flex-grow-1">
            <label for="official-source">Official source by content category</label>
            <select id="official-source" name="source" class="form-control" required>
                @foreach($categories as $kind => $label)
                    @php($categorySources = array_filter($sources, fn ($source) => $source['kind'] === $kind))
                    @if($categorySources)
                        <optgroup label="{{ $label }}">
                            @foreach($categorySources as $key => $source)
                                <option value="{{ $key }}" @selected(old('source') === $key)>{{ $label }}: {{ $source['name'] }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-sync-alt mr-2"></i>Check Source</button>
    </form>
    <p class="text-muted small mt-3 mb-0">New resources enter Pending review. Repeated source checks preserve existing reviews. Approved references are not published to learners.</p>
</div>
<div class="ltd-panel">
    @include('admin.crud.partials.table-controls', ['items' => $imports, 'sortOptions' => ['created_at' => 'Date added', 'title' => 'Title', 'status' => 'Review status', 'fetched_at' => 'Date fetched'], 'filters' => [
        'status' => ['label' => 'Review status', 'options' => ['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected']],
        'kind' => ['label' => 'Content category', 'options' => $categories],
    ]])
    <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Resource</th><th>Source</th><th>Added by</th><th>Status</th><th>Review</th></tr></thead><tbody>
    @forelse($imports as $item)
        <tr>
            <td style="min-width:200px;max-width:350px;overflow-wrap:anywhere"><a href="{{ route('learningContent.show', $item) }}">{{ $item->title }}</a><div class="small text-muted">{{ $categories[$item->kind] ?? $item->kind }}</div></td>
            <td style="max-width:260px;overflow-wrap:anywhere">{{ $item->source_name }}<div class="small text-muted">{{ $item->fetched_at->format('M j, Y') }}</div></td>
            <td>{{ $item->creator?->name ?? ($item->created_by ? 'Former admin' : 'System') }}</td>
            <td><span class="badge badge-{{ $item->status === 'Pending' ? 'warning' : ($item->status === 'Approved' ? 'success' : 'secondary') }}">{{ $item->status }}</span></td>
            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('learningContent.show', $item) }}">{{ $item->status === 'Pending' ? 'Review' : 'View review' }}</a></td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-center text-muted py-4">No resources match. Check an official source or adjust your filters.</td></tr>
    @endforelse
    </tbody></table></div>
    @include('admin.crud.partials.table-pagination', ['items' => $imports])
</div>
@endsection
