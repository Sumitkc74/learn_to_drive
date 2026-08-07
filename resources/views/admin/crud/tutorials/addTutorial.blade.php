@extends('admin.layout.master')

@section('title', 'Add Tutorial')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    @include('admin.layout.flash')
    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Add Tutorial</h1>
        </div>
    </div>

    <div class="ltd-panel">
        <form role="form" action="{{ URL::to('/admin/insert-tutorial') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="title">Tutorial Title</label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" placeholder="Enter tutorial title" value="{{ old('title') }}">
                @error('title')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" rows="4" name="description" placeholder="Enter tutorial description">{{ old('description') }}</textarea>
                @error('description')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="videoLink">Tutorial Link</label>
                <input type="text" class="form-control @error('videoLink') is-invalid @enderror" name="videoLink" placeholder="Enter tutorial link" value="{{ old('videoLink') }}">
                @error('videoLink')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="image">Tutorial Image</label>
                <input type="file" name="image" class="form-control-file @error('image') is-invalid @enderror">
                @error('image')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="/admin" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@section('page-script')
    <script type='text/javacript'>

    </script>
@endsection
