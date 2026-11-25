<section class="ltd-form-section">
    <h2 class="ltd-form-section__title">Upload PDF</h2>
    <div class="row">
        <div class="col-md-4 form-group"><label class="ltd-field-label" for="file-language">Language category</label><select id="file-language" name="language" class="form-control" required><option value="English" @selected(old('language', $examInformation->language ?? 'English') === 'English')>English</option><option value="Nepali" @selected(old('language', $examInformation->language ?? 'English') === 'Nepali')>Nepali</option></select></div>
        <div class="col-md-8 form-group"><label class="ltd-field-label" for="bank-pdf">PDF file</label><x-upload-field name="pdf" id="bank-pdf" accept=".pdf,application/pdf" :required="!($editing ?? false)" :max-size-kb="\App\Models\AppSetting::documentLimitKb()" :invalid="$errors->has('pdf')" />@error('pdf')<div class="text-danger">{{ $message }}</div>@enderror</div>
    </div>
    <span class="ltd-field-help">Choose the language of this PDF. Maximum {{ number_format(\App\Models\AppSetting::documentLimitKb()/1024, 1) }} MB.@if($editing ?? false) Leave the file empty to keep the current PDF. Uploading replaces this record's PDF.@endif</span>
</section>
