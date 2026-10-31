@extends('admin.layout.master')
@section('title', 'CSV / Excel imports')
@section('content')
<div class="ltd-page-header"><h1>CSV / Excel imports</h1></div>
<section class="ltd-panel mb-3">
    <form method="get"><label for="import-type">Import section</label><select id="import-type" name="type" class="form-control">@foreach(\App\Support\AdminImportCatalog::TYPES as $key => $info)<option value="{{ $key }}" @selected($type === $key)>{{ $info[0] }}</option>@endforeach</select><button class="btn btn-primary mt-2">Choose section</button></form>
    <p class="mt-3">Existing imports: <a href="{{ route('questionImport') }}">Questions</a> · <a href="{{ route('noticeImport') }}">Notices</a></p>
</section>
<section class="ltd-panel">
    <h2>{{ $definition[0] }}</h2>
    <p><a href="{{ route('bulkImport.template', ['type' => $type, 'format' => 'csv']) }}">Download CSV template</a> · <a href="{{ route('bulkImport.template', ['type' => $type, 'format' => 'xlsx']) }}">Download Excel template</a></p>
    <p>Use the exact template headers. UTF-8 CSV and .xlsx are supported (first worksheet, plain values, no formulas). Save old .xls files as .xlsx first. Up to 500 rows; all rows are validated before anything is saved. Existing records are skipped, never overwritten.</p>
    <p><strong>Columns:</strong> {{ implode(', ', $definition[2]) }}</p>
    @if($type === 'users')<p>Roles: User or PremiumUser. Phone numbers must have 10 digits. Passwords are generated privately; users must use Forgot password to choose their password. Configure email delivery first. Importing does not send emails or verify email addresses. Administrator accounts must be created individually.</p>@endif
    @if(in_array($type, ['question-banks','exam-information']))<p>Language: English or Nepali. The name and description must match that language. Upload the PDF named in the attachment column.</p>@endif
    @if($type === 'schools')<p>owner_email must belong to an existing active non-admin account. Schools start Pending and need admin approval. Latitude and longitude are optional but must be provided together.</p>@endif
    @if($type === 'school-memberships')<p>Use an approved school's numeric ID and an existing active non-admin account email. Roles: Student, Instructor or Manager. Existing memberships keep their current roles.</p>@endif
    <form method="post" action="{{ route('bulkImport.store', $type) }}" enctype="multipart/form-data">@csrf
        <label for="import-file">Spreadsheet</label><input id="import-file" type="file" name="file" accept=".csv,.xlsx" class="form-control mb-3" required>
        @if(in_array('attachment', $definition[2], true))
        <p>Put the exact filename in each attachment cell, then upload those files below. No URLs or server paths. Reusing an attachment across rows is supported. Keep batches within your server's upload limits.</p>
        <label for="import-attachments">Images or PDFs</label><input id="import-attachments" type="file" name="attachments[]" multiple accept="{{ in_array($type, ['question-banks','exam-information']) ? '.pdf' : '.jpg,.jpeg,.png,.webp' }}" class="form-control mb-3">
        @endif
        <button class="btn btn-primary">Validate and import</button>
    </form>
</section>
@endsection
