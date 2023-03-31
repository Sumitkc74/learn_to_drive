@extends('admin.layout.master')

@section('title', 'Add Tutorial')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <div class="card-body">
        <form role="form" action="{{ URL::to('/admin/insert-tutorial') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
                <label for="title" class="col-sm-2 col-form-label">Tutorial Title :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('title')) is-invalid @endif"  name="title" placeholder="Enter tutorial title" value="{{ old('title') }}">
                    @if($errors->has('title'))
                        <p class="text-danger">{{ $errors->first('title') }}</p>
                    @endif
                </div>
            </div>
            <div class="form-group row">
                <label for="description" class="col-sm-2 col-form-label">Description :</label>
                <div class="col-sm-10">
                    <textarea type="text" class="form-control  @if($errors->has('description')) is-invalid @endif" rows="4" name="description" placeholder="Enter tutorial description">{{ old('description') }}</textarea>
                    @if($errors->has('description'))
                        <p class="text-danger">{{ $errors->first('description') }}</p>
                    @endif
                </div>
            </div>
            <div class="form-group row">
                <label for="videoLink" class="col-sm-2 col-form-label">Tutorial Link :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('videoLink')) is-invalid @endif"  name="videoLink" placeholder="Enter tutorial link" value="{{ old('videoLink') }}">
                    @if($errors->has('videoLink'))
                        <p class="text-danger">{{ $errors->first('videoLink') }}</p>
                    @endif
                </div>
            </div>

            <div class="card-footer">
            <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
@endsection

@section('page-script')
    <script type='text/javacript'>

    </script>
@endsection
