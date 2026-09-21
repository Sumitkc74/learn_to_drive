> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# English / Nepali PDF translation

Open Learning Content Review → Upload one PDF for translation, or open an existing
saved question-bank reference. Select the original language and Start Translation.
The background PDF worker processes up to 100 pages, with a 20 MB source limit.
Text extraction falls back to full-page Nepali/English OCR for scanned or legacy-font pages.

Review each translated page against its original image. Correct OCR/translation
errors, particularly question numbers, option labels and answer markings. Blank or
image-only pages can be described in the translated text. Approve the original
reference with its language/category/edition, then Generate Reviewed PDF.
The generated reference is approved after all pages are reviewed, but is not
published. Use Add to Exam Papers to pair it with the original.

Output includes original page images and translated text on separate pages. This
preserves diagrams for comparison, but does not recreate the original layout or
translate labels inside diagrams. It is explicitly labelled a reviewed translation,
not an official translated edition. Source and output must fit the saved upload limits.

## Provider setup

### Gemini API

Create a key in [Google AI Studio](https://aistudio.google.com/apikey), then set these
values in your private `.env`:

```dotenv
PDF_TRANSLATION_DRIVER=gemini
GEMINI_API_KEY=your-key
GEMINI_TRANSLATION_MODEL=gemini-3.5-flash
```

Use a generateContent-compatible text model available to your account; the model
ID is configurable. Clear configuration and restart the PDF worker using the
commands below. The existing translation/review workflow then uses Gemini.
Requests send extracted text with separate translation instructions. Truncated,
blocked and failed responses are rejected rather than saved as complete pages.
Model thought parts are excluded. No key or document text is logged on failure.
API usage is subject to your account's pricing and quotas; it is not automatically
free. Live Gemini translation still needs your key and has not been tested here.
[Gemini REST API reference](https://ai.google.dev/api)

No provider credentials or model were supplied during implementation. Translation
is disabled until configured. Do not commit secrets.

Google Cloud Translation Basic:

```dotenv
PDF_TRANSLATION_DRIVER=google
GOOGLE_TRANSLATION_API_KEY=your-key
```

The key needs Cloud Translation access and the service must be enabled in its
project. The confirmation form explains that extracted text goes to Google and
may incur charges. No requests are made just by uploading a PDF.
[Google REST reference](https://docs.cloud.google.com/translate/docs/reference/rest/v2/translate)

Alternatively, run a model supporting English and Nepali through local Ollama:

```dotenv
PDF_TRANSLATION_DRIVER=local
PDF_TRANSLATION_LOCAL_URL=http://127.0.0.1:11434
PDF_TRANSLATION_LOCAL_MODEL=your-installed-model
```

Only loopback endpoints are accepted. Model availability and translation quality
must be checked locally; no model was downloaded automatically.
[Ollama generate API](https://docs.ollama.com/api/generate)

After changing configuration:

```powershell
php artisan config:clear
php artisan queue:restart
powershell -ExecutionPolicy Bypass -File scripts/start-pdf-worker.ps1
```

PDF generation uses headless Edge on this Windows setup and system Devanagari
fonts. Set `PDF_TRANSLATION_BROWSER` to a compatible Chromium/Edge executable on
other hosts and install a Devanagari font such as Noto Sans Devanagari.
OCR uses the existing local Python/Tesseract installation.

Validated: provider request direction and failure handling with HTTP fakes,
authenticated review/publishing guards, real local PDF extraction, and real PDF
generation with visually checked English and Nepali text. Live provider translation
has not been tested because credentials/model configuration are still needed.
