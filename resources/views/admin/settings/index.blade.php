@extends('admin.layout.master')
@section('title', 'Application Settings')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">System Administration</span><h1>Application Settings</h1></div></div>
<div class="ltd-form-shell"><form class="ltd-form-card" method="POST" action="{{ route('appSettings.update') }}" data-add-form>@csrf @method('PATCH')
    <div class="ltd-form-card__intro"><i class="fas fa-sliders-h"></i><div><h2>System configuration</h2><p>Control learner exam rules, verification timing, and upload limits from one place.</p></div></div>
    <div class="ltd-form-card__body">
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">Exam rules</h3><div class="row">
            @foreach([['exam_duration_minutes','Exam duration','minutes',5,180],['exam_passing_score','Passing score','%',1,100],['exam_question_count','Questions per exam','questions',5,100]] as [$field,$label,$suffix,$min,$max])
            <div class="col-md-4 form-group"><label class="ltd-field-label" for="{{ $field }}">{{ $label }}</label><div class="input-group"><input id="{{ $field }}" type="number" name="{{ $field }}" value="{{ old($field, $settings[$field]) }}" min="{{ $min }}" max="{{ $max }}" class="form-control @error($field) is-invalid @enderror" required><div class="input-group-append"><span class="input-group-text">{{ $suffix }}</span></div></div>@error($field)<div class="text-danger small">{{ $message }}</div>@enderror</div>
            @endforeach
        </div></section>
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">Verification and uploads</h3><div class="row">
            @foreach([['otp_expiry_minutes','Phone OTP expiry','minutes',2,30],['image_upload_limit_mb','Image limit','MB',1,10],['document_upload_limit_mb','Document limit','MB',1,50]] as [$field,$label,$suffix,$min,$max])
            <div class="col-md-4 form-group"><label class="ltd-field-label" for="{{ $field }}">{{ $label }}</label><div class="input-group"><input id="{{ $field }}" type="number" name="{{ $field }}" value="{{ old($field, $settings[$field]) }}" min="{{ $min }}" max="{{ $max }}" class="form-control @error($field) is-invalid @enderror" required><div class="input-group-append"><span class="input-group-text">{{ $suffix }}</span></div></div>@error($field)<div class="text-danger small">{{ $message }}</div>@enderror</div>
            @endforeach
        </div></section>
        <section class="ltd-form-section"><h3 class="ltd-form-section__title">Learner notice</h3><label class="ltd-field-label" for="maintenance_notice">Maintenance or service message</label><textarea id="maintenance_notice" name="maintenance_notice" rows="3" maxlength="500" class="form-control @error('maintenance_notice') is-invalid @enderror" placeholder="Leave empty when there is no service notice.">{{ old('maintenance_notice', $settings['maintenance_notice']) }}</textarea>@error('maintenance_notice')<div class="invalid-feedback">{{ $message }}</div>@enderror<small class="form-text text-muted">The learner application can retrieve this through the configuration API.</small></section>
    </div><div class="ltd-form-actions"><button class="btn btn-primary" type="submit" data-submit-button><i class="fas fa-save mr-2"></i>Save Settings</button></div>
</form></div>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
