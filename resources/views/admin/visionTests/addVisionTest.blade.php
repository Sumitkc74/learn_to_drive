@extends('admin.layout.master')

@section('title', 'Vision Test')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <div class="card-body">
        <form role="form" action="{{ URL::to('/admin/insert-exam-papers') }}" method="post">
            @csrf
            <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">Test number :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="name" placeholder="Enter vision-test number" required>
                </div>
            </div>
            {{-- <div class="form-group row">
                <label for="description" class="col-sm-2 col-form-label">Description :</label>
                <div class="col-sm-10">
                    <textarea type="text" class="form-control" rows="4" name="description" placeholder="Enter vision-test description" required></textarea>
                </div>
            </div> --}}
            <div class="form-group row">
                <label for="inputImage" class="col-sm-2 col-form-label">Input Test Image :</label>
                <div class="col-sm-10 input-group">
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="inputImage" required>
                        <label class="custom-file-label" for="inputImage">Choose Image</label>
                    </div>
                    <div class="input-group-append">
                        <span class="input-group-text">Upload</span>
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
