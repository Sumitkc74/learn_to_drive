@extends('admin.layout.master')
@section('title', 'Media Library')
@section('content')
<p><a href="{{ route('storageCheck') }}" class="btn btn-outline-primary">Storage Check</a></p>
<div class="ltd-page-header"><div><h1>Media Library</h1><p class="text-muted">Images and PDFs attached to learning content. Open the related item to add or replace its files.</p></div><a class="btn btn-outline-secondary" href="{{ route('learningContent') }}">Private Review Files</a></div>
<div class="ltd-panel mb-4"><form method="GET" class="d-flex flex-wrap align-items-end" style="gap:.75rem">
<label>Filename<input name="search" maxlength="100" value="{{ request('search') }}" class="form-control" placeholder="Search files"></label>
<label>Content<select name="type" class="form-control"><option value="">All content</option>@foreach(\App\Http\Controllers\Admin\MediaLibraryController::TYPES as $key=>$type)<option value="{{ $key }}" @selected(request('type')===$key)>{{ $type[1] }}</option>@endforeach</select></label>
<label>Format<select name="format" class="form-control"><option value="">All formats</option><option value="image" @selected(request('format')==='image')>Images</option><option value="pdf" @selected(request('format')==='pdf')>PDFs</option></select></label>
<button class="btn btn-primary mb-2">Filter</button><a class="btn btn-outline-secondary mb-2" href="{{ route('mediaLibrary') }}">Reset</a>
</form><p class="small text-muted mt-2 mb-0">{{ $files->total() }} matching files. Availability is checked for the files on this page. Private review downloads and user profile pictures are kept separately.</p></div>
<div class="row">
@forelse($rows as $row)
@php($media=$row['media'])
<div class="col-md-6 col-xl-4 mb-4"><article class="ltd-panel h-100" style="overflow-wrap:anywhere">
@if($row['exists'] === true)
    @if($row['image'])<a href="{{ $media->getUrl() }}" target="_blank" rel="noopener noreferrer"><img src="{{ $media->getUrl() }}" alt="{{ $media->file_name }}" loading="lazy" style="width:100%;height:160px;object-fit:contain" onerror="this.hidden=true; this.parentElement.nextElementSibling.hidden=false;"></a><p class="text-warning" hidden>Preview could not load. Check the storage link and application URL.</p>
    @else<div class="text-center py-4"><i class="fas fa-file-pdf fa-3x" aria-hidden="true"></i><p>{{ $media->mime_type }}</p></div>@endif
@elseif($row['exists'] === false)<p class="text-danger">File missing from storage. Open the content item to upload a replacement.</p>
@else<p class="text-warning">Storage could not be checked. Try again later.</p>@endif
<h2 class="h6 mt-3">{{ $media->file_name }}</h2><p class="small text-muted">{{ $row['label'] }} · {{ number_format($media->size/1024,1) }} KB · {{ $media->created_at->format('M j, Y') }}</p>
<p>{{ \Illuminate\Support\Str::limit($row['title'],100) }}</p>
@if($media->getCustomProperty('language'))<p class="small">Language: {{ $media->getCustomProperty('language') }}</p>@endif
<div class="d-flex flex-wrap" style="gap:.5rem">@if($row['exists'] === true)<a class="btn btn-sm btn-outline-primary" href="{{ $media->getUrl() }}" target="_blank" rel="noopener noreferrer">Open File</a>@endif
@if($row['edit'])<a class="btn btn-sm btn-primary" href="{{ $row['edit'] }}">Edit Content</a>@else<span class="text-muted">Related item is deleted or unavailable.</span>@endif</div>
</article></div>
@empty<div class="col-12"><div class="ltd-panel text-center text-muted">No files match. Add an image or PDF through its content page.</div></div>@endforelse
</div>{{ $files->links() }}
@endsection
