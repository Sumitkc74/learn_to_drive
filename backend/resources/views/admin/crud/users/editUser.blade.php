@extends('admin.layout.master')

@section('title', 'Edit User')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
<div class="ltd-page-header"><div><h1>Edit User</h1></div></div>
<div class="ltd-panel mb-3"><span class="text-muted">Added by</span> @include('admin.crud.partials.creator', ['record' => $edit])</div>

    <div class="card-body">
        <form action="{{ URL::to('/admin/update-user/'.$edit->id) }}" method="post" enctype="multipart/form-data">
            @csrf
            <!-- User Name -->
            <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">User Name :</label>
                <div class="col-sm-10">
                    <input id="name" type="text" class="form-control @if($errors->has('name')) is-invalid @endif" name="name" placeholder="Enter user name" value="{{ $edit->name }}" >
                    @if($errors->has('name'))
                        <p class="text-danger">{{ $errors->first('name') }}</p>
                    @endif
                </div>
            </div>

            <!-- User Email -->
            <div class="form-group row">
                <label for="email" class="col-sm-2 col-form-label">Email :</label>
                <div class="col-sm-10">
                    <input id="email" type="email" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" placeholder="Enter user email" value="{{ $edit->email }}">
                    @if($errors->has('email'))
                        <p class="text-danger">{{ $errors->first('email') }}</p>
                    @endif
                </div>
            </div>

            <!-- User Phone Number -->
            <div class="form-group row">
                <label for="phoneNumber" class="col-sm-2 col-form-label">Phone Number :</label>
                <div class="col-sm-10">
                    <input id="phoneNumber" type="number" class="form-control @if($errors->has('phoneNumber')) is-invalid @endif" name="phoneNumber" placeholder="Enter user phone-number" value="{{ $edit->phoneNumber }}" required>
                    @if($errors->has('phoneNumber'))
                        <p class="text-danger">{{ $errors->first('phoneNumber') }}</p>
                    @endif
                </div>
            </div>

            <!-- User Password -->
            {{-- <div class="form-group row">
                <label for="password" class="col-sm-2 col-form-label">Password :</label>
                <div class="col-sm-10">
                    <input id="password" type="password" class="form-control @if($errors->has('password')) is-invalid @endif" name="password" placeholder="Enter password" value="{{ $edit->password }}">
                    @if($errors->has('password'))
                        <p class="text-danger">{{ $errors->first('password') }}</p>
                    @endif
                </div>
            </div> --}}

            <!-- User Role -->
            <div class="form-group row">
                <label for="role" class="col-sm-2 col-form-label">Role :</label>
                <div class="col-sm-10">
                    <select class="form-control" id="role" name="role" required>
                        <option value="User" {{ 'User' == $edit->role ? 'selected' : '' }}>User</option>
                        <option value="PremiumUser" {{ 'PremiumUser' == $edit->role ? 'selected' : '' }}>Premium User</option>
                        @if(auth()->user()->is_seed_admin)
                            <option value="Admin" {{ 'Admin' == $edit->role ? 'selected' : '' }}>Admin</option>
                        @endif
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-10 offset-sm-2">
                    <x-upload-field name="profileImage" id="profileImage" label="Profile image" :current="$edit->avatar_url" current-alt="{{ $edit->name }} profile photo" :max-size-kb="\App\Models\AppSetting::imageLimitKb()" :invalid="$errors->has('profileImage')" />
                    @if($errors->has('profileImage'))
                        <p class="text-danger">{{ $errors->first('profileImage') }}</p>
                    @endif
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
