> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# PDF review tools

## Admin workflow

Open **Learning Content Review → a saved question bank → Extract and Review Questions**.

- Filter candidates by status, missing answer, possible diagram, incomplete options, or OCR origin. Counts describe the whole bank; filters combine with status and remain in pagination links.
- Open a candidate to see the source PDF page beside the edit form. Previous/Next, page number, zoom, and **Question page** help inspect rows continued onto another page. On smaller screens, the preview appears above the form. **Open full size** opens a private rendered PNG, and Download Source PDF remains available as a fallback.
- **Nepali PDF OCR** reads one page per request. The saved A/K and B Nepali banks start questions on physical PDF page 5. OCR reads the ruled table row by row, with question numbers separated from question text. It recognizes क/ख/ग/घ and A/B/C/D option labels.
- Every OCR candidate requires manual text/answer review. No answer ticks are inferred by OCR. Unknown labels, incomplete options, low confidence and possible diagrams are flagged. An unreadable question number uses the table row number and carries an explicit warning. Images and cross-page OCR continuations require manual preparation. Borderless/irregular tables may yield no candidates.
- Import always creates a Draft. OCR provenance and source checksum remain attached to the candidate. Re-running OCR preserves existing candidates and completed reviews. OCR and text extraction have separate fingerprints; do not extract both modes solely to inflate the bank. The ordinary question duplicate check still applies at import.

The local trial read A/K PDF pages 5–6 and added 16 Pending Nepali candidates. This is a trial, not a claim that every OCR word or detected question number is correct. No trial questions were imported or published.

## Local Windows setup

This checkout has the required packages and Nepali/English models installed privately. No paid API or document upload service is used. The helper uses [pypdfium2](https://github.com/pypdfium2-team/pypdfium2) for rasterization and [tesserocr](https://github.com/sirfz/tesserocr) with Tesseract for OCR. The Windows wheel is from the [Windows build project referenced by tesserocr](https://github.com/simonflueckiger/tesserocr-windows_build). Language models come from the official [tessdata_fast repository](https://github.com/tesseract-ocr/tessdata_fast).

For another Windows checkout, install Python **3.12 x64**, then run from the project root:

```powershell
powershell -File scripts/setup-ocr.ps1
```

The setup installs pinned dependencies under `storage/app/private/ocr-python` and checks the language-model SHA-256 values before using them. It does not install a global Tesseract executable. Network access is required only during installation.

Optional environment settings are `PDF_OCR_PYTHON` (default `python`), `PDF_OCR_MODULES` and `PDF_OCR_TESSDATA`. The latter two default to this checkout's private storage paths. If PHP cannot find Python on its PATH, set `PDF_OCR_PYTHON` to the full executable path, clear Laravel's configuration cache and restart the PHP server.

For Linux/macOS, use a compatible Python environment with pypdfium2/Pillow and tesserocr built against the host's Tesseract libraries, install the Nepali and English models, and configure the executable/model paths. The bundled Windows wheel is platform specific.

Check the setup:

```powershell
python scripts/pdf_tools.py check --modules storage/app/private/ocr-python --tessdata storage/app/private/ocr-tessdata
```

For a longer OCR run, the CLI processes one page at a time and reports progress:

```powershell
php artisan content:ocr-questions 1 --from=5 --to=6
```

Replace `1` with the learning-resource ID. Each run accepts at most 100 pages. Each page has a 120-second processing timeout, a 20-megapixel raster cap, and a 20 MB source-file cap. The web endpoint processes one page per request. Page previews use a 45-second timeout and a private checksum-keyed cache; source changes invalidate preview access. Only authenticated admins may access either endpoint.
