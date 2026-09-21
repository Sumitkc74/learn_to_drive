> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Exam Information

Each entry contains one PDF, an English or Nepali language category, one title,
and one description. Write both text fields in the PDF's selected language.
The list supports filtering by language. Editing replaces only that entry's PDF;
leaving the upload blank preserves it.

Migration 2026_09_14_000003 separates older paired PDFs while preserving their media
paths and creator attribution. Nepali entries use the former Nepali title when
available. Existing descriptions are preserved and may need translation by an admin.

The API `/api/examInformation` retains `data.examInformations` and its media response,
and now provides `name`, `description`, `language`, and `pdf_url` for each entry.
The old `nepaliName`, `englishFile`, and `nepaliFile` fields are removed.

New entries and edits validate the title and description against the selected language.
Nepali text must predominantly use Devanagari letters; English text uses Latin letters.
Numbers, punctuation, and short category codes in Nepali text are allowed. This is a
script check, not automatic translation or detection of the PDF language. Reviewed
Question Bank imports use the approved source language for the same validation.
