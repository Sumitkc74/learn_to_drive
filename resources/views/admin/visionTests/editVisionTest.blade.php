@extends('admin.layout.master')

@section('title', 'Edit Vision Test')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <div class="card-body">
        <form role="form" action="{{ URL::to('/admin/update-vision-test/'.$edit->id) }}" method="post"  enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">Vision Test Number :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="name" placeholder="Enter vision-test number" required value="{{ $edit->name }}" >
                </div>
            </div>
            {{-- <div class="form-group row">
                <label for="email" class="col-sm-2 col-form-label">Description :</label>
                <div class="col-sm-10">
                    <input type="email" class="form-control" name="email" placeholder="Enter vision-test description" required value="{{ $edit->email }}">
                </div>
            </div> --}}
                <div class="form-group">
                    <label for="inputFile">Input File</label>
                    <div class="input-group">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="inputFile" required>
                            <label class="custom-file-label" for="inputFile">Choose File</label>
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
