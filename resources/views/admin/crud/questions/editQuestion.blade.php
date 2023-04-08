@extends('admin.layout.master')

@section('title', 'Edit Question')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <div class="card-body">
        <form role="form" action="{{ URL::to('/admin/update-question/'.$edit->id) }}" method="post">
            @csrf
            <!-- Question -->
            <div class="form-group row">
                <label for="question" class="col-sm-2 col-form-label">Question :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('question')) is-invalid @endif" name="question" placeholder="Enter question" value="{{ $edit->question }}" >
                    @if($errors->has('question'))
                        <p class="text-danger">{{ $errors->first('question') }}</p>
                    @endif
                </div>
            </div>

            <!-- Option 1 -->
            <div class="form-group row">
                <label for="option1" class="col-sm-2 col-form-label">Option A :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('option1')) is-invalid @endif" name="option1" placeholder="Enter Option 1" value="{{ $edit->option1 }}">
                    @if($errors->has('option1'))
                        <p class="text-danger">{{ $errors->first('option1') }}</p>
                    @endif
                </div>
            </div>

            <!-- Option 2 -->
            <div class="form-group row">
                <label for="option2" class="col-sm-2 col-form-label">Option B :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('option2')) is-invalid @endif" name="option2" placeholder="Enter option 2" value="{{ $edit->option2 }}">
                    @if($errors->has('option2'))
                        <p class="text-danger">{{ $errors->first('option2') }}</p>
                    @endif
                </div>
            </div>

            <!-- Option 3 -->
            <div class="form-group row">
                <label for="option3" class="col-sm-2 col-form-label">Option C :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('option3')) is-invalid @endif" name="option3" placeholder="Enter option 3" value="{{ $edit->option3 }}">
                    @if($errors->has('option3'))
                        <p class="text-danger">{{ $errors->first('option3') }}</p>
                    @endif
                </div>
            </div>

            <!-- Option 4 -->
            <div class="form-group row">
                <label for="option4" class="col-sm-2 col-form-label">Option D :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('option4')) is-invalid @endif" name="option4" placeholder="Enter option 4" value="{{ $edit->option4 }}">
                    @if($errors->has('option4'))
                        <p class="text-danger">{{ $errors->first('option4') }}</p>
                    @endif
                </div>
            </div>

            <!-- Correct Option -->
            <div class="form-group row">
                <label for="correctOption" class="col-sm-2 col-form-label">Correct Option :</label>
                <div class="col-sm-10">
                    <select class="form-control" id="exampleFormControlSelect1" name="correctOption" required>
                        <option value="A" {{ 'A' == $edit->correctOption ? 'selected' : '' }}>Option A</option>
                        <option value="B" {{ 'B' == $edit->correctOption ? 'selected' : '' }}>Option B</option>
                        <option value="C" {{ 'C' == $edit->correctOption ? 'selected' : '' }}>Option C</option>
                        <option value="D" {{ 'D' == $edit->correctOption ? 'selected' : '' }}>Option D</option>
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
