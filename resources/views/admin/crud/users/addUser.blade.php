@extends('admin.layout.master')
@section('title', 'Add User')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">User Management</span><h1>Add User</h1></div></div>
<div class="ltd-form-shell">
    <form class="ltd-form-card" action="{{ route('insertUser') }}" method="POST" enctype="multipart/form-data" data-add-form>
        @csrf
        <div class="ltd-form-card__intro"><i class="fas fa-user-plus"></i><div><h2>Create a user account</h2><p>Enter account details and assign only the access level this person needs.</p></div></div>
        <div class="ltd-form-card__body">
            <section class="ltd-form-section"><h3 class="ltd-form-section__title">Personal information</h3><div class="row">
                <div class="col-md-6 form-group"><label class="ltd-field-label" for="name">Full name <span class="ltd-required">*</span></label><input id="name" name="name" value="{{ old('name') }}" maxlength="255" autocomplete="name" class="form-control @error('name') is-invalid @enderror" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6 form-group"><label class="ltd-field-label" for="phoneNumber">Phone number <span class="ltd-required">*</span></label><input id="phoneNumber" name="phoneNumber" value="{{ old('phoneNumber') }}" inputmode="numeric" maxlength="10" autocomplete="tel" class="form-control @error('phoneNumber') is-invalid @enderror" placeholder="98XXXXXXXX" required>@error('phoneNumber')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            </div></section>
            <section class="ltd-form-section"><h3 class="ltd-form-section__title">Sign-in and access</h3><div class="row">
                <div class="col-md-6 form-group"><label class="ltd-field-label" for="email">Email <span class="ltd-required">*</span></label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" class="form-control @error('email') is-invalid @enderror" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6 form-group"><label class="ltd-field-label" for="role">Role <span class="ltd-required">*</span></label><select id="role" name="role" class="form-control @error('role') is-invalid @enderror" required>@php($roles = auth()->user()->is_seed_admin ? ['User'=>'User','PremiumUser'=>'Premium User','Admin'=>'Admin'] : ['User'=>'User','PremiumUser'=>'Premium User'])@foreach($roles as $value=>$label)<option value="{{ $value }}" {{ old('role', 'User') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>@error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror<span class="ltd-field-help">Only the seed administrator can assign the Admin role.</span></div>
                <div class="col-md-6 form-group"><label class="ltd-field-label" for="password">Password <span class="ltd-required">*</span></label><input id="password" type="password" name="password" minlength="8" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6 form-group"><label class="ltd-field-label" for="password_confirmation">Confirm password <span class="ltd-required">*</span></label><input id="password_confirmation" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" class="form-control" required></div>
            </div></section>
            <section class="ltd-form-section"><h3 class="ltd-form-section__title">Profile photo <span class="text-muted">(optional)</span></h3><div class="ltd-file-field"><input type="file" name="profileImage" accept=".jpg,.jpeg,.png,.webp" data-image-input="#userImagePreview" class="form-control-file @error('profileImage') is-invalid @enderror">@error('profileImage')<div class="text-danger small mt-2">{{ $message }}</div>@enderror<span class="ltd-field-help">JPEG, PNG or WebP up to 2 MB. The default avatar is used when no photo is selected.</span><img id="userImagePreview" class="ltd-image-preview has-image" src="{{ asset('dist/img/avatar.png') }}" alt="Profile photo preview"></div></section>
        </div>
        <div class="ltd-form-actions"><a href="{{ route('allUser') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit" data-submit-button><i class="fas fa-user-plus mr-2"></i>Add User</button></div>
    </form>
</div>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
