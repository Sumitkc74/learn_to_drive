@extends('admin.layout.master')

@section('title', 'Exam Papers')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <div class="card-body">
        <form role="form" action="{{ URL::to('/admin/insert-exam-information') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">Exam information :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('name')) is-invalid @endif"  name="name" placeholder="Enter exam-information" value="{{ old('name') }}">
                </div>
            </div>
            <div class="form-group row">
                <label for="description" class="col-sm-2 col-form-label">Description :</label>
                <div class="col-sm-10">
                    <textarea type="text" class="form-control  @if($errors->has('description')) is-invalid @endif" rows="4" name="description" placeholder="Enter exam-information description">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-10 input-group">
                    <label for="file" class="col-sm-2 col-form-label">Input File :</label>
                    <div class="col-sm-10">
                        <input type="file" name="file" class="form-control @if($errors->has('file')) is-invalid @endif">
                        @if($errors->has('name'))
                            <p class="text-danger">{{ $errors->first('file') }}</p>
                        @endif
                    </div>
                </div>
            </div>


            {{-- <div class="form-group row">
                <label for="inputFile" class="col-sm-2 col-form-label">Input File :</label>
                <div class="col-sm-10 input-group">
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="inputFile" required>
                        <label class="custom-file-label" for="inputFile">Choose file</label>
                    </div>
                    <div class="input-group-append">
                        <span class="input-group-text">Upload</span>
                    </div>
                </div>
            </div> --}}
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
