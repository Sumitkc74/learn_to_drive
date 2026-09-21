@if($summary = session('import_summary'))
<div class="alert alert-info" role="status">
    <strong>Import summary:</strong>
    Added: {{ $summary['added'] }} &middot; Skipped: {{ $summary['skipped'] }} &middot;
    Duplicate: {{ $summary['duplicate'] }} &middot; Invalid: {{ $summary['invalid'] }}.
    @if($summary['invalid']) No records were added; fix the invalid rows and upload again. @endif
</div>
@endif
@if($errors->any())
<div class="alert alert-danger" role="alert" id="admin-error-alert">
    <strong>{{ $errors->first() }}</strong>
</div>
@endif

@if ($message = Session::get('success'))
<div class="alert alert-success alert-block" role="status" id="admin-success-alert">
    <strong>{{ $message }}</strong>
</div>
<script>
    setTimeout(function() {
        $('#admin-success-alert').fadeOut('fast');
    }, 3000); // the duration is set to 3 seconds (3000 milliseconds)
</script>
@endif
