@extends('admin.layout.master')

@section('title', 'Add Question')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Add Question</h1>
        </div>
    </div>

    <div class="ltd-panel">
        <form role="form" action="{{ URL::to('/admin/insert-question') }}" method="post">
            @csrf
            <div class="form-group">
                <label for="question">Question</label>
                <input type="text" class="form-control @error('question') is-invalid @enderror" name="question" placeholder="Enter the question" value="{{ old('question') }}">
                @error('question')<p class="text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="option1">Option A</label>
                    <input type="text" class="form-control @error('option1') is-invalid @enderror" name="option1" placeholder="Enter option 1" value="{{ old('option1') }}">
                    @error('option1')<p class="text-danger mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label for="option2">Option B</label>
                    <input type="text" class="form-control @error('option2') is-invalid @enderror" name="option2" placeholder="Enter option 2" value="{{ old('option2') }}">
                    @error('option2')<p class="text-danger mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="option3">Option C</label>
                    <input type="text" class="form-control @error('option3') is-invalid @enderror" name="option3" placeholder="Enter option 3" value="{{ old('option3') }}">
                    @error('option3')<p class="text-danger mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label for="option4">Option D</label>
                    <input type="text" class="form-control @error('option4') is-invalid @enderror" name="option4" placeholder="Enter option 4" value="{{ old('option4') }}">
                    @error('option4')<p class="text-danger mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-group">
                <label for="correctOption">Correct Option</label>
                <select class="form-control" name="correctOption">
                    <option value="A">Option A</option>
                    <option value="B">Option B</option>
                    <option value="C">Option C</option>
                    <option value="D">Option D</option>
                </select>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="/admin/questions" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@section('page-script')
    <script type='text/javacript'>

    </script>
@endsection
