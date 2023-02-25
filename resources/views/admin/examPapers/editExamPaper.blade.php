@extends('admin.layout.master')

@section('title', 'Edit Exam Paper')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <div class="card-body">
        <form role="form" action="{{ URL::to('/admin/update-exam-paper/'.$edit->id) }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">Exam Paper Name :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('name')) is-invalid @endif" name="name" placeholder="Enter exam-paper name" value="{{ $edit->name }}" >
                </div>
            </div>

            <div class="form-group row">
                <label for="description" class="col-sm-2 col-form-label">Descriptioin :</label>
                <div class="col-sm-10">
                    <textarea type="text" class="form-control  @if($errors->has('description')) is-invalid @endif" rows="4" name="description" placeholder="Enter exam-paper description">
                        {{ $edit->description }}
                    </textarea></textarea>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-10 input-group">
                    <label for="file" class="col-sm-2 col-form-label">Input Exam File :</label>
                    <div class="col-sm-10">
                        <input type="file" name="file" class="form-control @if($errors->has('file')) is-invalid @endif" value="{{ $edit->file }}">
                        @if($errors->has('name'))
                            <p class="text-danger">{{ $errors->first('file') }}</p>
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
