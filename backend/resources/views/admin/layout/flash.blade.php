{{-- Accessible flash region: every message stays visible until the next page
     load (nothing auto-dismisses, so screen-reader and keyboard users can
     read it at their own pace). Validation errors list every problem, not
     just the first one. --}}
@if($summary = session('import_summary'))
<div class="alert alert-info" role="status">
    <strong>Import summary:</strong>
    Added: {{ $summary['added'] }} &middot; Skipped: {{ $summary['skipped'] }} &middot;
    Duplicate: {{ $summary['duplicate'] }} &middot; Invalid: {{ $summary['invalid'] }}.
    @if($summary['invalid']) No records were added; fix the invalid rows and upload again. @endif
</div>
@endif
@if($errors->any())
<div class="alert alert-danger" role="alert" id="admin-validation-errors">
    <strong id="admin-validation-errors-heading">Please fix the following {{ $errors->count() === 1 ? 'problem' : 'problems' }}:</strong>
    <ul class="mb-0 mt-2" aria-labelledby="admin-validation-errors-heading">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if ($message = Session::get('success'))
<div class="alert alert-success alert-block" role="status" id="admin-success-alert">
    <strong>{{ $message }}</strong>
</div>
@endif

@if ($message = Session::get('error'))
<div class="alert alert-danger alert-block" role="alert" id="admin-session-error-alert">
    <strong>{{ $message }}</strong>
</div>
@endif
