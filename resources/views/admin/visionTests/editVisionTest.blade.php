@extends('admin.layout.master')

@section('title', 'Edit Vision Test')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <div class="card-body">
        <form role="form" action="{{ URL::to('/admin/update-vision-test/'.$visionTest->id) }}" method="post"  enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">Vision Test Number :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="name" placeholder="Enter vision-test number" required value="{{ $visionTest->number }}" >
                </div>
            </div>
            {{-- <div class="form-group row">
                <label for="email" class="col-sm-2 col-form-label">Description :</label>
                <div class="col-sm-10">
                    <input type="email" class="form-control" name="email" placeholder="Enter vision-test description" required value="{{ $edit->email }}">
                </div>
            </div> --}}

            <div class="form-group row">
                <div class="col-sm-10 input-group">
                    <label for="image" class="col-sm-2 col-form-label">Input Sign Image :</label>
                    <img src="{{ $visionTest->getFirstMediaUrl() }}" height="200" width="200">
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
