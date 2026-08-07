@extends('admin.layout.master')

@section('title', 'Add Traffic-Signs')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    @include('admin.layout.flash')
    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Add Traffic Sign</h1>
        </div>
    </div>

    <div class="ltd-panel">
        <form role="form" action="{{ URL::to('/admin/insert-traffic-sign') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="name">Traffic Sign</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Enter traffic-sign" value="{{ old('name') }}">
                @error('name')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="nepaliSignName">Nepali Sign Name</label>
                <input type="text" class="form-control @error('nepaliSignName') is-invalid @enderror" name="nepaliSignName" placeholder="Enter nepali name" value="{{ old('nepaliSignName') }}">
                @error('nepaliSignName')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" rows="4" name="description" placeholder="Enter traffic-sign description">{{ old('description') }}</textarea>
                @error('description')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="image">Sign Image</label>
                <input type="file" name="image" class="form-control-file @error('image') is-invalid @enderror">
                @error('image')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Add</button>
                <a href="/admin" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@section('page-script')
    <script type='text/javacript'>

    </script>
@endsection
