> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Individual question banks

Each question bank record has one PDF and a required language category: English or Nepali.
Use Add Question Bank, enter the details, select the language, and upload one PDF.
The list displays Language and PDF columns and supports filtering by language.
Editing replaces only that record?s PDF. Leaving the upload blank keeps it.

Approved Learning Content PDFs (including reviewed translations) are added individually
using Add to Question Banks. The language comes from the reviewed source. Approval,
checksum, size, MIME and duplicate checks remain required. Private sources are retained.
There is no attach-another-language action; each file is a separate bank.

Migration 2026_09_14_000001 adds language, splits legacy paired records, reassigns media
without moving PDF files, and removes englishFile and nepaliFile from exam_papers.
Descriptions, creator attribution and source metadata are preserved.

API /api/examPaper now returns language and pdf_url for each bank, plus existing media.
Clients must use these fields instead of english_pdf_url/nepali_pdf_url or paired files.

Each record has a single title (`name`) and description, written in the selected PDF
language. There is no separate Nepali title field. Migration 2026_09_14_000002 uses
the existing Nepali title for Nepali banks (falling back to the current title if empty)
and removes `nepaliName`. Existing descriptions are retained without automatic translation.
The API returns `name`, `description`, `language`, and `pdf_url`.

New entries and edits validate the title and description against the selected language.
Nepali text must predominantly use Devanagari letters; English text uses Latin letters.
Numbers, punctuation, and short category codes in Nepali text are allowed. This is a
script check, not automatic translation or detection of the PDF language. Reviewed
Question Bank imports use the approved source language for the same validation.
