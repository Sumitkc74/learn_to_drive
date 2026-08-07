@extends('admin.layout.master')

@section('title', 'Profile Settings')

@section('content')
    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Profile Settings</h1>
        </div>
    </div>

    <div class="ltd-panel">
        @if($user)
        <div class="mb-4 p-3 rounded border" style="background: rgba(255, 222, 23, 0.08);">
            <h5 class="mb-1">Manage your admin account</h5>
            <p class="mb-0 text-muted">Update your personal details, email address, and password from the sections below.</p>
        </div>

        <form action="{{ route('updateProfileSettings') }}" method="POST">
            @csrf

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-2">Account Settings</h5>
                    <p class="text-muted small mb-3">Update your basic account information.</p>

                    <div class="form-group row">
                        <label for="name" class="col-sm-3 col-form-label">Full Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <p class="text-danger mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row mt-3">
                        <label for="phoneNumber" class="col-sm-3 col-form-label">Phone Number</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control @error('phoneNumber') is-invalid @enderror" name="phoneNumber" value="{{ old('phoneNumber', $user->phoneNumber) }}" required>
                            @error('phoneNumber')
                                <p class="text-danger mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-2">Change Email</h5>
                    <p class="text-muted small mb-3">Use a valid email address that you want to receive admin notifications on.</p>

                    <div class="form-group row">
                        <label for="email" class="col-sm-3 col-form-label">Email Address</label>
                        <div class="col-sm-9">
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <p class="text-danger mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-2">Change Password</h5>
                    <p class="text-muted small mb-3">Leave this blank if you do not want to change your current password.</p>

                    <div class="form-group row">
                        <label for="password" class="col-sm-3 col-form-label">New Password</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="Enter a new password">
                            @error('password')
                                <p class="text-danger mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="/admin" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
        @else
            <p class="mb-0">Please log in to update your profile.</p>
        @endif
    </div>
@endsection
