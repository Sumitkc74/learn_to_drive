@extends('admin.layout.master')

@section('title', 'Add Notice')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    @include('admin.layout.flash')
    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Add Notice</h1>
        </div>
    </div>

    <div class="ltd-panel">
        <form role="form" action="{{ URL::to('/admin/insert-notice') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="title">Notice Title</label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" placeholder="Enter notice title" value="{{ old('title') }}">
                @error('title')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" rows="4" name="description" placeholder="Enter notice description">{{ old('description') }}</textarea>
                @error('description')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="nepaliTitle">Nepali Title</label>
                <input type="text" class="form-control @error('nepaliTitle') is-invalid @enderror" name="nepaliTitle" placeholder="Enter nepali title" value="{{ old('nepaliTitle') }}">
                @error('nepaliTitle')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="nepaliDescription">Nepali Description</label>
                <textarea class="form-control @error('nepaliDescription') is-invalid @enderror" rows="4" name="nepaliDescription" placeholder="Enter nepali description">{{ old('nepaliDescription') }}</textarea>
                @error('nepaliDescription')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="link">Notice Link</label>
                <input type="text" class="form-control @error('link') is-invalid @enderror" name="link" placeholder="Enter notice link" value="{{ old('link') ?? '' }}">
                @error('link')<p class="text-danger mt-1">{{ $message }}</p>@enderror
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
