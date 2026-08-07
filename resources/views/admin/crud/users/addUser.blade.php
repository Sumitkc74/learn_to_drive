@extends('admin.layout.master')

@section('title', 'Add User')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Add User</h1>
        </div>
    </div>

    <div class="ltd-panel">
        <form role="form" action="{{ URL::to('/admin/insert-user') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="name">User Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Enter user name" value="{{ old('name') }}">
                    @error('name')<p class="text-danger mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" placeholder="Enter user email" value="{{ old('email') }}">
                    @error('email')<p class="text-danger mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="phoneNumber">Phone Number</label>
                    <input type="text" class="form-control @error('phoneNumber') is-invalid @enderror" name="phoneNumber" placeholder="Enter user phone-number" value="{{ old('phoneNumber') }}">
                    @error('phoneNumber')<p class="text-danger mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="Enter password">
                    @error('password')<p class="text-danger mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="role">Role</label>
                    <select class="form-control" name="role">
                        <option value="User">User</option>
                        <option value="PremiumUser">Premium User</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label for="profileImage">Profile Image</label>
                    <input type="file" name="profileImage" class="form-control-file @error('profileImage') is-invalid @enderror">
                    @error('profileImage')<p class="text-danger mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="/admin/users" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@section('page-script')
    <script type='text/javacript'>

    </script>
@endsection
