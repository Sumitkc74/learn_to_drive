@extends('admin.layout.master')

@section('title', 'Edit Vision Test')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
<div class="ltd-page-header"><div><h1>Edit Vision Test</h1></div></div>
<div class="ltd-panel mb-3"><span class="text-muted">Added by</span> @include('admin.crud.partials.creator', ['record' => $visionTest])</div>

    <div class="card-body">
        <form action="{{ URL::to('/admin/update-vision-test/'.$visionTest->id) }}" method="post"  enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
                <label for="testNumber" class="col-sm-2 col-form-label">Test Number :</label>
                <div class="col-sm-10">
                    <input id="testNumber" type="text" class="form-control  @if($errors->has('testNumber')) is-invalid @endif" name="testNumber" placeholder="Enter vision-test" value="{{ $visionTest->testNumber }}">
                    @if($errors->has('testNumber'))
                        <p class="text-danger">{{ $errors->first('testNumber') }}</p>
                    @endif
                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-10 input-group">
                    <label for="image" class="col-sm-2 col-form-label">Input Test Image :</label>
                    <div class="col-sm-10">
                        <x-upload-field name="image" id="image" :current="$visionTest->getFirstMediaUrl()" current-alt="Current vision test image" :max-size-kb="\App\Models\AppSetting::imageLimitKb()" :invalid="$errors->has('image')" />
                        @if($errors->has('image'))
                            <p class="text-danger">{{ $errors->first('image') }}</p>
                        @endif
                    </div>
                </div>
            </div>

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
