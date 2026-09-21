<section class="ltd-form-section">
    <h3 class="ltd-form-section__title">Upload PDF</h3>
    <div class="row">
        <div class="col-md-4 form-group"><label for="file-language">Language category</label><select id="file-language" name="language" class="form-control" required><option value="English" @selected(old('language', $examInformation->language ?? 'English') === 'English')>English</option><option value="Nepali" @selected(old('language', $examInformation->language ?? 'English') === 'Nepali')>Nepali</option></select></div>
        <div class="col-md-8 form-group"><label for="bank-pdf">PDF file</label><input id="bank-pdf" name="pdf" type="file" accept=".pdf,application/pdf" class="form-control-file" @required(!($editing ?? false))>@error('pdf')<div class="text-danger">{{ $message }}</div>@enderror</div>
    </div>
    <p class="small text-muted">Choose the language of this PDF. Maximum {{ number_format(\App\Models\AppSetting::documentLimitKb()/1024, 1) }} MB.@if($editing ?? false) Leave the file empty to keep the current PDF. Uploading replaces this record?s PDF.@endif</p>
</section>
