@extends('admin.layout.master')

@section('title', 'Edit Notice')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <div class="card-body">
        <form role="form" action="{{ URL::to('/admin/update-notice/'.$notice->id) }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
                <label for="title" class="col-sm-2 col-form-label">Notice Title :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('title')) is-invalid @endif" name="title" placeholder="Enter notice title" value="{{ $notice->title }}" >
                    @if($errors->has('title'))
                        <p class="text-danger">{{ $errors->first('title') }}</p>
                    @endif
                </div>
            </div>

            <div class="form-group row">
                <label for="description" class="col-sm-2 col-form-label">Descriptioin :</label>
                <div class="col-sm-10">
                    <textarea type="text" class="form-control  @if($errors->has('description')) is-invalid @endif" rows="4" name="description" placeholder="Enter notice description">
                        {{ $notice->description }}
                    </textarea>
                    @if($errors->has('description'))
                        <p class="text-danger">{{ $errors->first('description') }}</p>
                    @endif
                </div>
            </div>

            <div class="form-group row">
                <label for=nepaliTitle" class="col-sm-2 col-form-label">Nepali Title :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('nepaliTitle')) is-invalid @endif" name="nepaliTitle" placeholder="Enter nepali notice title" value="{{ $notice->nepaliTitle }}" >
                    @if($errors->has('nepaliTitle'))
                        <p class="text-danger">{{ $errors->first('nepaliTitle') }}</p>
                    @endif
                </div>
            </div>

            <div class="form-group row">
                <label for="nepaliDescription" class="col-sm-2 col-form-label">Nepali Descriptioin :</label>
                <div class="col-sm-10">
                    <textarea type="text" class="form-control  @if($errors->has('nepaliDescription')) is-invalid @endif" rows="4" name="nepaliDescription" placeholder="Enter nepali description">
                        {{ $notice->nepaliDescription }}
                    </textarea>
                    @if($errors->has('nepaliDescription'))
                        <p class="text-danger">{{ $errors->first('nepaliDescription') }}</p>
                    @endif
                </div>
            </div>

            <div class="form-group row">
                <label for="link" class="col-sm-2 col-form-label">Link :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('link')) is-invalid @endif" name="link" placeholder="Enter notice link" value="{{ $notice->link }}" >
                    {{-- @if($errors->has('link'))
                        <p class="text-danger">{{ $errors->first('link') }}</p>
                    @endif --}}
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
