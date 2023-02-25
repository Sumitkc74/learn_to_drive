@extends('admin.layout.master')

@section('title', 'Add User')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <div class="card-body">
        <form role="form" action="{{ URL::to('/admin/insert-user') }}" method="post">
            @csrf
            <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">User Name :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control  @if($errors->has('name')) is-invalid @endif" name="name" placeholder="Enter user name" value="{{ old('name') }}">
                    @if($errors->has('name'))
                        <p class="text-danger">{{ $errors->first('name') }}</p>
                    @endif
                </div>
            </div>
            <div class="form-group row">
                <label for="email" class="col-sm-2 col-form-label">Email :</label>
                <div class="col-sm-10">
                    <input type="email" class="form-control  @if($errors->has('email')) is-invalid @endif" name="email" placeholder="Enter user email" value="{{ old('email') }}">
                    @if($errors->has('email'))
                        <p class="text-danger">{{ $errors->first('email') }}</p>
                    @endif
                </div>
            </div>
            <div class="form-group row">
                <label for="phoneNumber" class="col-sm-2 col-form-label">Phone Number :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('phoneNumber')) is-invalid @endif" name="phoneNumber" placeholder="Enter user phone-number" value="{{ old('phoneNumber') }}">
                    @if($errors->has('phoneNumber'))
                        <p class="text-danger">{{ $errors->first('phoneNumber') }}</p>
                    @endif
                </div>
            </div>
            <div class="form-group row">
                <label for="password" class="col-sm-2 col-form-label">Password :</label>
                <div class="col-sm-10">
                    <input type="password" class="form-control @if($errors->has('password')) is-invalid @endif" name="password" placeholder="Enter password">
                    @if($errors->has('password'))
                        <p class="text-danger">{{ $errors->first('password') }}</p>
                    @endif
                </div>
            </div>
            <div class="form-group row">
                <label for="role" class="col-sm-2 col-form-label">Role :</label>
                <div class="col-sm-10">
                    <select class="form-control" id="exampleFormControlSelect1" name="role">
                        <option value="User">User</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
            </div>
                {{-- <div class="form-group">
                    <label for="exampleInputFile">File input</label>
                    <div class="input-group">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="exampleInputFile" required>
                            <label class="custom-file-label" for="exampleInputFile">Choose file</label>
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
