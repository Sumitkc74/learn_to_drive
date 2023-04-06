@extends('admin.layout.master')

@section('title', 'Edit Trafic-Signs')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection


@section('content')
    <div class="card-header">
    </div>
    <div class="card-body">
        <form role="form" action="{{ URL::to('/admin/update-traffic-sign/'.$trafficSign->id) }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">Traffic Sign :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control  @if($errors->has('name')) is-invalid @endif" name="name" placeholder="Enter traffic-sign" value="{{ $trafficSign->name }}">
                    @if($errors->has('name'))
                        <p class="text-danger">{{ $errors->first('name') }}</p>
                    @endif
                </div>
            </div>

            <div class="form-group row">
                <label for="nepaliSignName" class="col-sm-2 col-form-label">Nepali Sign-Name :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control  @if($errors->has('nepaliSignName')) is-invalid @endif" name="nepaliSignName" placeholder="Enter nepali sign-name" value="{{ $trafficSign->nepaliSignName }}">
                    @if($errors->has('nepaliSignName'))
                        <p class="text-danger">{{ $errors->first('nepaliSignName') }}</p>
                    @endif
                </div>
            </div>

            <div class="form-group row">
                <label for="description" class="col-sm-2 col-form-label">Description :</label>
                <div class="col-sm-10">
                    <textarea type="text" class="form-control @if($errors->has('description')) is-invalid @endif" rows="4" name="description" placeholder="Enter traffic-sign description">
                        {{ $trafficSign->description }}
                    </textarea>
                    @if($errors->has('description'))
                        <p class="text-danger">{{ $errors->first('description') }}</p>
                    @endif
                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-10 input-group">
                    <label for="image" class="col-sm-2 col-form-label">Input Sign Image :</label>
                    <img src="{{ $trafficSign->getFirstMediaUrl() }}" height="200" width="200">
                    <div class="col-sm-10">
                        <input type="file" name="image">
                        {{-- class="@if($errors->has('image')) is-invalid @endif"
                        @if($errors->has('image'))
                            <p class="text-danger">{{ $errors->first('image') }}</p>
                        @endif --}}
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
@endsection

@section('page-script')
    <script type='text/javacript'>

    </script>
@endsection
