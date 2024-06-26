@extends('admin.layout.master')

@section('title', 'Vision Test')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    @include('admin.layout.flash')
    <div class="card-body">
        <form role="form" action="{{ URL::to('/admin/insert-vision-test') }}" method="post"  enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
                <label for="testNumber" class="col-sm-2 col-form-label">Test number :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('testNumber')) is-invalid @endif" name="testNumber" placeholder="Enter vision-test number">
                    @if($errors->has('testNumber'))
                        <p class="text-danger">{{ $errors->first('testNumber') }}</p>
                    @endif
                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-10 input-group">
                    <label for="image" class="col-sm-2 col-form-label">Input Test Image :</label>
                    <div class="col-sm-10">
                        <input type="file" name="image" class="@if($errors->has('image')) is-invalid @endif">
                        @if($errors->has('image'))
                            <p class="text-danger">{{ $errors->first('image') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- <div class="form-check">
                <input type="checkbox" class="form-check-input" id="exampleCheck1">
                <label class="form-check-label" for="exampleCheck1">Check me out</label>
            </div> --}}
            <!-- /.card-body -->

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
