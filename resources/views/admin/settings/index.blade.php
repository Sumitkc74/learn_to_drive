@extends('admin.layout.master')
@section('title', 'Application Settings')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">System Administration</span><h1>Application Settings</h1><p class="text-muted mb-0">Update one application component without affecting the others.</p></div></div>

<div class="ltd-settings-grid">
    <form id="question-bank" class="ltd-settings-card" method="POST" action="{{ route('appSettings.update', 'question-bank') }}">@csrf @method('PATCH')
        <div class="ltd-settings-card__heading"><i class="fas fa-question-circle"></i><div><h2>Question Bank &amp; Exams</h2><p>Control mock-test structure and passing requirements.</p></div></div>
        <div class="ltd-settings-card__body">
            @foreach([['exam_duration_minutes','Exam duration','minutes',5,180],['exam_passing_score','Passing score','%',1,100],['exam_question_count','Questions per exam','questions',5,100]] as [$field,$label,$suffix,$min,$max])
            <div class="form-group"><label class="ltd-field-label" for="{{ $field }}">{{ $label }}</label><div class="input-group"><input id="{{ $field }}" type="number" name="{{ $field }}" value="{{ old($field, $settings[$field]) }}" min="{{ $min }}" max="{{ $max }}" class="form-control @error($field) is-invalid @enderror" required><div class="input-group-append"><span class="input-group-text">{{ $suffix }}</span></div></div>@error($field)<div class="text-danger small">{{ $message }}</div>@enderror</div>
            @endforeach
        </div><div class="ltd-settings-card__actions"><button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i>Save Question Bank</button></div>
    </form>

    <form id="verification" class="ltd-settings-card" method="POST" action="{{ route('appSettings.update', 'verification') }}">@csrf @method('PATCH')
        <div class="ltd-settings-card__heading"><i class="fas fa-shield-alt"></i><div><h2>Verification</h2><p>Configure verification-code security and timing.</p></div></div>
        <div class="ltd-settings-card__body"><div class="form-group"><label class="ltd-field-label" for="otp_expiry_minutes">Phone OTP expiry</label><div class="input-group"><input id="otp_expiry_minutes" type="number" name="otp_expiry_minutes" value="{{ old('otp_expiry_minutes', $settings['otp_expiry_minutes']) }}" min="2" max="30" class="form-control @error('otp_expiry_minutes') is-invalid @enderror" required><div class="input-group-append"><span class="input-group-text">minutes</span></div></div>@error('otp_expiry_minutes')<div class="text-danger small">{{ $message }}</div>@enderror</div></div>
        <div class="ltd-settings-card__actions"><button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i>Save Verification</button></div>
    </form>

    <form id="users" class="ltd-settings-card" method="POST" action="{{ route('appSettings.update', 'users') }}">@csrf @method('PATCH')
        <div class="ltd-settings-card__heading"><i class="fas fa-users-cog"></i><div><h2>User Accounts</h2><p>Control how long new learner API sessions remain valid.</p></div></div>
        <div class="ltd-settings-card__body"><div class="form-group"><label class="ltd-field-label" for="access_token_expiry_days">API session lifetime</label><div class="input-group"><input id="access_token_expiry_days" type="number" name="access_token_expiry_days" value="{{ old('access_token_expiry_days', $settings['access_token_expiry_days']) }}" min="1" max="365" class="form-control @error('access_token_expiry_days') is-invalid @enderror" required><div class="input-group-append"><span class="input-group-text">days</span></div></div>@error('access_token_expiry_days')<div class="text-danger small">{{ $message }}</div>@enderror<small class="form-text text-muted">Existing tokens retain their current expiration date.</small></div></div>
        <div class="ltd-settings-card__actions"><button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i>Save User Settings</button></div>
    </form>

    <form id="uploads" class="ltd-settings-card" method="POST" action="{{ route('appSettings.update', 'uploads') }}">@csrf @method('PATCH')
        <div class="ltd-settings-card__heading"><i class="fas fa-cloud-upload-alt"></i><div><h2>Uploads</h2><p>Set global image and document size limits.</p></div></div>
        <div class="ltd-settings-card__body">
            @foreach([['image_upload_limit_mb','Image upload limit',1,10],['document_upload_limit_mb','Document upload limit',1,50]] as [$field,$label,$min,$max])
            <div class="form-group"><label class="ltd-field-label" for="{{ $field }}">{{ $label }}</label><div class="input-group"><input id="{{ $field }}" type="number" name="{{ $field }}" value="{{ old($field, $settings[$field]) }}" min="{{ $min }}" max="{{ $max }}" class="form-control @error($field) is-invalid @enderror" required><div class="input-group-append"><span class="input-group-text">MB</span></div></div>@error($field)<div class="text-danger small">{{ $message }}</div>@enderror</div>
            @endforeach
        </div><div class="ltd-settings-card__actions"><button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i>Save Upload Limits</button></div>
    </form>

    <form id="general" class="ltd-settings-card ltd-settings-card--wide" method="POST" action="{{ route('appSettings.update', 'general') }}">@csrf @method('PATCH')
        <div class="ltd-settings-card__heading"><i class="fas fa-bullhorn"></i><div><h2>General &amp; Service Notice</h2><p>Show a temporary maintenance or service message in the learner application.</p></div></div>
        <div class="ltd-settings-card__body"><div class="form-group mb-0"><label class="ltd-field-label" for="maintenance_notice">Learner-facing message</label><textarea id="maintenance_notice" name="maintenance_notice" rows="3" maxlength="500" class="form-control @error('maintenance_notice') is-invalid @enderror" placeholder="Leave empty when there is no service notice.">{{ old('maintenance_notice', $settings['maintenance_notice']) }}</textarea>@error('maintenance_notice')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
        <div class="ltd-settings-card__actions"><button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i>Save General Settings</button></div>
    </form>
</div>
@endsection
