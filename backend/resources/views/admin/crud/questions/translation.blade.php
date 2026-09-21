<section class="ltd-form-section mb-3" data-question-translation data-url="{{ route('questionTranslation') }}">
    <h3 class="ltd-form-section__title">Translate question and answers</h3>
    @if(app(\App\Services\PdfTextTranslator::class)->ready())
    <div class="d-flex flex-wrap align-items-end" style="gap:.75rem">
        <label class="mb-0">Translation direction
            <select class="form-control" data-translation-direction><option value="ne">Nepali → English</option><option value="en">English → Nepali</option></select>
        </label>
        <button type="button" class="btn btn-outline-primary" data-translation-start>Translate All Details</button>
        <button type="button" class="btn btn-outline-secondary" data-translation-cancel hidden>Cancel Translation</button>
    </div>
    <p class="small text-muted mt-2">Translates the question, options A–D, and explanation using {{ match(config('pdf-translation.driver')) { 'gemini' => 'Gemini', 'google' => 'Google Cloud Translation', default => 'your local model' } }}. Review the results before applying them. The correct-answer letter, category, difficulty, and image stay unchanged. Text inside images is not translated.</p>
    @if(config('pdf-translation.driver') !== 'local')<p class="small text-muted">Text is sent to the configured provider; its API usage limits and charges apply.</p>@endif
    <p role="status" aria-live="polite" data-translation-status></p>
    <div data-translation-preview hidden>
        <h4>Review translated details</h4>
        <div data-translation-fields></div>
        <button type="button" class="btn btn-primary" data-translation-apply>Apply to Form</button>
        <button type="button" class="btn btn-outline-secondary" data-translation-discard>Discard Translation</button>
        <p class="small text-muted mt-2">Apply replaces the text in this form. Save or import the question afterward to keep the changes.</p>
    </div>
    @else<p class="text-muted">Configure Gemini, Google Cloud Translation, or a local model to translate question details.</p>@endif
</section>
@once
@push('scripts')<script src="{{ asset('dist/js/question-translation.js') }}" defer></script>@endpush
@endonce
