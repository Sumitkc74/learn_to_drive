> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Official learning-content discovery trial

Checked locally on 2026-09-09. This is a preview-only trial: no managed records are created, no files are attached to content, and nothing is published or scheduled.

## Findings

| Source | Potential use | Live discovery result |
| --- | --- | --- |
| [Kalanki Transport Management Office question collections](https://tmokalanki.bagamati.gov.np/pages/question-and-answer-collection-12/) | Question-bank reference documents; later extraction into Questions | Three unique PDF URLs: A/K Nepali, B Nepali, B English. A duplicate iframe/download link becomes one resource. One non-approved/non-HTTPS link excluded. |
| [Nepal Traffic Police traffic signs](https://traffic.nepalpolice.gov.np/about-us/traffic-signs/) | Traffic Signs | Four same-host image URLs: three composite sign sheets and one additional image. Four old external image links excluded. Composite sheets need splitting and label review before becoming individual signs. |
| [DoTM Category B, 2082-83](https://www.dotm.gov.np/content/111/-b--written-examination-questions-for-class-2082-83/) | Check the current question-bank edition | Page loaded, but no directly discoverable PDF attachment was found by this parser. |
| [DoTM Category A/K, 2082-83](https://www.dotm.gov.np/content/112/written-test-questions-2082-83-for--ak--class/) | Check the current question-bank edition | Page loaded, but no directly discoverable PDF attachment was found by this parser. |

The government CDN was accepted only for documents linked from these official transport pages. A representative English PDF returned HTTP 200, `application/pdf`, and a PDF file signature. The regulatory sign sheet returned HTTP 200, `image/jpeg`, and a JPEG signature. These were small streaming probes, not full downloads or question extraction.

Other candidates investigated:

- [Department of Roads traffic signs](https://dor.gov.np/mo-phidim/publication/traffic-safety/traffic-signs-nepali): found in search, but the page could not be opened in the research browser. Not enabled in the trial.
- [Traffic Police E-Challan information](https://traffic.nepalpolice.gov.np/notices/63/): contains a tutorial-video reference. Not yet tested for video extraction; this is service guidance rather than driving-test instruction.
- Exam Information can use transport-office guidance, with an explicit edition/date and geographic scope. Do not treat an office-specific schedule as a national rule.
- No verified reusable vision-test dataset was established in this trial.

The available question-bank documents must be compared against the current edition before use. They are question collections, not verified past exam papers. Correct-answer columns and questions containing sign images require manual checking; do not infer missing answers or explanations.

## Run the trial

```bash
php artisan content:preview-official --save
php artisan content:preview-official --source=kalanki --save
php artisan content:preview-official --source=traffic-police
```

On this Windows checkout, the temporary Windows trust-store export can be supplied without disabling certificate verification:

```powershell
php -d "curl.cainfo=$env:TEMP\learn-to-drive-ca.pem" -d "openssl.cafile=$env:TEMP\learn-to-drive-ca.pem" artisan content:preview-official --save
```

The report is written to `storage/app/private/official-content-preview.json` on the default local disk; each saved run replaces the previous report. It records source URLs, asset URLs, URL fingerprints, excluded links, and failures. `review_required` is a preview flag, not a database review-queue entry. `asset_downloaded` remains false because discovery does not download the assets.

Source keys and exact host allowlists are in `config/official-content.php`. The command rejects arbitrary source URLs, does not follow redirects, caps source HTML at 2 MB, does not execute page scripts, and is not scheduled. One failed source is reported without preventing the remaining sources from being checked; any fetch failure gives a nonzero exit code.

## Learning-content review queue

The admin sidebar and Dashboard Admin Tools now link to **Learning Content Review** at `/admin/learning-content`.

1. Choose an official source and click **Check Source**. New resources enter Pending; duplicate URLs preserve their existing review status.
2. Open a resource and select **Save Private Copy for Review**. PDFs and raster images are downloaded into the private `learning-content` disk, checked by actual file type and saved upload limits, and assigned a SHA-256 checksum. Redirects and hosts outside the selected source allowlist are rejected.
3. Download and inspect the private copy. Record its readable title, language, licence category, edition (or explicitly Unknown), and review notes.
4. Select **Approve Reference**, or reject with a reason. The reviewer comes from the authenticated admin; completed decisions cannot be overwritten by another reviewer or a repeat fetch. Audit logs record collection, downloads, and decisions.

Approval marks a source reference as reviewed. It does **not** create or publish Questions, Exam Papers, or Traffic Signs. Saved sign sheets have a separate **Extract Individual Signs** screen for manual cropping, labeling, and saving individual signs. Saved PDFs have **Extract and Review Questions**, described below.

## PDF question extraction

For review filters, side-by-side page previews, and the local Nepali OCR workflow, see [PDF review tools](pdf-review-tools.md). The limitations below describe the original direct-text extraction profile; Nepali ruled-table PDFs can now be processed through the separate OCR action.

Install locked dependencies with `composer install` and run `php artisan migrate`. Text extraction uses [smalot/pdfparser](https://github.com/smalot/pdfparser), pinned in Composer. No OCR service or API key is used.

1. Open a saved question-bank PDF in Learning Content Review and click **Extract and Review Questions**.
2. Enter physical PDF page numbers (up to 100 at a time). The default range covers the saved 57-page English Category B bank.
3. Review each candidate against the downloadable original. Names, spelling, options, answers, categories and difficulty remain editable. Answer suggestions use tick coordinates aligned with the English DoTM A–D table columns, not general knowledge. Ambiguous answers stay blank.
4. Confirm the review and **Import as Draft**, or reject the candidate. Draft questions need any required diagram added before publishing through Questions. No batch publishing is performed.

Candidates retain their source resource, PDF checksum, physical page, source question number, raw text and warnings. Reviewed decisions survive repeated extraction. Pending machine-generated candidates may be refreshed by another extraction; edits are submitted when importing. Identical questions, including those in trash, are blocked at import. Source/review attribution is recorded in audit logs.

The current parsing profile targets numbered English DoTM tables, including options continued on the next page. It does not OCR scanned pages, decode legacy Nepali fonts, or extract diagrams. Empty/unsupported pages are reported in the extraction summary. Missing or ambiguous fields require correction before import. The 20 MB file limit and decompression limit bound normal extraction size; use small page ranges for slower hosts.

The local English Category B trial (the PDF labeled 2078, not the newer 2082/83 bank) produced 500 Pending candidates from 57 physical pages. It suggested 288 answers; 212 were left blank. Question 452 repeats option C in the source and is flagged with a missing option D. The two cover pages and final instructions page produced no candidates. Repeating extraction added no duplicates. No learner questions were created by this trial.

Follow-up check: continuation pages without A–D headings now reuse the last complete, correctly ordered table header within the extraction range. Ticks must still match both a column X coordinate and the question Y coordinate; an incomplete/reordered header clears the inherited layout. Re-extraction increased answer suggestions to 394 (106 additional suggestions), leaving 106 blank and preserving completed reviews. These remain suggestions requiring comparison with the PDF. The two saved Nepali banks were inspected but not imported: text mappings corrupt letters and omit question numbers. Newer DoTM source pages timed out during the follow-up check. No additional distinct question bank was added.

To populate the queue from the terminal:

```bash
php artisan migrate
php artisan content:fetch-official --source=kalanki
php artisan content:fetch-official --source=traffic-police
```

`content:fetch-official --source=all` checks all configured sources, reporting failures independently. It is not scheduled. The earlier `content:preview-official` command remains a read-only discovery tool and does not populate this queue.

`OFFICIAL_CONTENT_CA_BUNDLE` optionally sets a trusted CA file for both discovery and downloads. This local Windows `.env` references the previously exported Windows trust bundle so browser-triggered checks work as well as CLI checks. Other deployments should leave it blank if their system CA store works; never disable certificate verification.

Migration: `2026_09_09_000001_create_learning_content_imports_table`. The local trial collected seven Pending resources and saved a private copy of the English Category B PDF (2,384,732 bytes). No resources were approved or published automatically.
