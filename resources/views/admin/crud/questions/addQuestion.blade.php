@extends('admin.layout.master')

@section('title', 'Add Question')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <div class="card-body">
        <form role="form" action="{{ URL::to('/admin/insert-question') }}" method="post">
            @csrf
            <div class="form-group row">
                <label for="question" class="col-sm-2 col-form-label">Question :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control  @if($errors->has('question')) is-invalid @endif" name="question" placeholder="Enter the question" value="{{ old('question') }}">
                    @if($errors->has('question'))
                        <p class="text-danger">{{ $errors->first('question') }}</p>
                    @endif
                </div>
            </div>

            <div class="form-group row">
                <label for="option1" class="col-sm-2 col-form-label">Option A :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control  @if($errors->has('option1')) is-invalid @endif" name="option1" placeholder="Enter option 1" value="{{ old('option1') }}">
                    @if($errors->has('option1'))
                        <p class="text-danger">{{ $errors->first('option1') }}</p>
                    @endif
                </div>
            </div>

            <div class="form-group row">
                <label for="option2" class="col-sm-2 col-form-label">Option B :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('option2')) is-invalid @endif" name="option2" placeholder="Enter option 2" value="{{ old('option2') }}">
                    @if($errors->has('option2'))
                        <p class="text-danger">{{ $errors->first('option2') }}</p>
                    @endif
                </div>
            </div>

            <div class="form-group row">
                <label for="option3" class="col-sm-2 col-form-label">Option C :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('option3')) is-invalid @endif" name="option3" placeholder="Enter option 3" value="{{ old('option3') }}">
                    @if($errors->has('option3'))
                        <p class="text-danger">{{ $errors->first('option3') }}</p>
                    @endif
                </div>
            </div>

            <div class="form-group row">
                <label for="option4" class="col-sm-2 col-form-label">Option D :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('option4')) is-invalid @endif" name="option4" placeholder="Enter option 4" value="{{ old('option4') }}">
                    @if($errors->has('option4'))
                        <p class="text-danger">{{ $errors->first('option4') }}</p>
                    @endif
                </div>
            </div>

            <div class="form-group row">
                <label for="correctOption" class="col-sm-2 col-form-label">Correct Option :</label>
                <div class="col-sm-10">
                    <select class="form-control" id="exampleFormControlSelect1" name="correctOption">
                        <option value="A">Option A</option>
                        <option value="B">Option B</option>
                        <option value="C">Option C</option>
                        <option value="D">Option D</option>
                    </select>
                </div>
            </div>
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
