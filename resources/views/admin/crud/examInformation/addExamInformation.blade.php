@extends('admin.layout.master')

@section('title', 'Edit Exam Information')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    @include('admin.layout.flash')
    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Add Exam Information</h1>
        </div>
    </div>

    <div class="ltd-panel">
        <form role="form" action="{{ URL::to('/admin/insert-exam-information') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="name">Exam Information Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Enter exam-information name" value="{{ old('name') }}">
                @error('name')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="nepaliName">Nepali Name</label>
                <input type="text" class="form-control @error('nepaliName') is-invalid @enderror" name="nepaliName" placeholder="Enter nepali-name" value="{{ old('nepaliName') }}">
                @error('nepaliName')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" rows="4" name="description" placeholder="Enter exam-information description">{{ old('description') }}</textarea>
                @error('description')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="englishFile">English File</label>
                    <input type="file" name="englishFile" class="form-control-file @error('englishFile') is-invalid @enderror">
                    @error('englishFile')<p class="text-danger mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label for="nepaliFile">Nepali File</label>
                    <input type="file" name="nepaliFile" class="form-control-file @error('nepaliFile') is-invalid @enderror">
                    @error('nepaliFile')<p class="text-danger mt-1">{{ $message }}</p>@enderror
                </div>
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
