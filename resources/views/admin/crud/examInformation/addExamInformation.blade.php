@extends('admin.layout.master')

@section('title', 'Edit Exam Information')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <div class="card-body">
        <form role="form" action="{{ URL::to('/admin/insert-exam-information') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">Exam information Name :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('name')) is-invalid @endif"  name="name" placeholder="Enter exam-information name" value="{{ old('name') }}">
                </div>
            </div>
            <div class="form-group row">
                <label for="nepaliName" class="col-sm-2 col-form-label">Nepali Name :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('nepaliName')) is-invalid @endif"  name="nepaliName" placeholder="Enter nepali-name" value="{{ old('nepaliName') }}">
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
                    <label for="englishFile" class="col-sm-2 col-form-label">English File :</label>
                    <div class="col-sm-10">
                        <input type="file" name="englishFile" @if($errors->has('englishFile')) is-invalid @endif">
                        @if($errors->has('englishFile'))
                            <p class="text-danger">{{ $errors->first('englishFile') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-10 input-group">
                    <label for="nepaliFile" class="col-sm-2 col-form-label">Nepali File :</label>
                    <div class="col-sm-10">
                        <input type="file" name="nepaliFile" @if($errors->has('nepaliFile')) is-invalid @endif">
                        @if($errors->has('nepaliFile'))
                            <p class="text-danger">{{ $errors->first('nepaliFile') }}</p>
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
