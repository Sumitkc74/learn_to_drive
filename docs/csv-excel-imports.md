# CSV and Excel imports

Open **Admin → System → CSV / Excel Imports** (`/admin/bulk-import`). Select the
record type, download a CSV or XLSX template, fill it in and upload it. Existing
question and notice import pages are linked from this hub.

| Section | Required values / behavior |
| --- | --- |
| Users | name, email, phoneNumber (10 digits), role (`User` or `PremiumUser`) |
| Tutorials | title, description, videoLink (HTTP/HTTPS), attachment filename |
| Traffic signs | name, nepaliSignName, description, image attachment filename |
| Vision tests | positive testNumber, image attachment filename |
| Question banks | name, description, language (`English`/`Nepali`), PDF filename |
| Exam information | name, description, language (`English`/`Nepali`), PDF filename |
| Driving schools | name, owner_email; optional contact fields and paired coordinates |
| School memberships | school_id, existing account email, role (`Student`/`Instructor`/`Manager`) |
| Questions | Existing Questions import template, including options and correct answer |
| Notices | Existing Notices import template, including bilingual text and publication fields |

Keep exact headers and ordering, including optional columns. CSV must be UTF-8;
XLSX uses the first worksheet and plain cell values. Old XLS and formula cells are
not supported. Format phone numbers as text to retain leading zeroes.

## Images and documents

Fill `attachment` with the exact filename, then select the corresponding files in
the upload form. Images: JPEG, PNG or WebP. Documents: PDF. URLs and server paths
are never downloaded/read. Reusing one uploaded filename across rows is allowed;
uploading two different files with the same name is rejected. Normal application
file-size limits apply. The PHP `max_file_uploads`, `upload_max_filesize` and
`post_max_size` settings can require smaller batches.

## Validation and duplicates

The shared imports accept up to 500 data rows and 100 attachments per request.
All rows and referenced files are checked before insertion. Invalid rows report
row numbers and prevent the batch from being saved. Successful imports run in a
transaction and record an audit summary. Blank rows and duplicates are counted.

Existing records are never overwritten. Duplicate keys are email for users,
title for tutorials, name for traffic signs/schools, testNumber for vision tests,
name + language for documents, and school ID + account for memberships. The first
valid occurrence in a batch wins. Existing question/notice rules and limits
remain unchanged.

Users receive random, hashed passwords that are not displayed or exported.
They must use Forgot password to set a password; working mail delivery is required.
Imports do not send messages, verify email, or create administrators. Schools
start Pending with an existing active non-admin owner and require approval.
Membership imports require approved schools and cannot overwrite existing roles.

Payments, refunds, bookings, audit entries, system settings and lesson schedules
are not arbitrary spreadsheet-import targets. Their dedicated flows enforce
financial, authorization and scheduling checks. This feature covers the listed
admin content/account sections; it does not expose a generic database importer.

PHP ZIP is required for XLSX; GD is needed for normal image processing. No new
database migration is required.

## Tests

From `backend/`:

```powershell
php -d extension=gd -d extension=zip vendor/phpunit/phpunit/phpunit --filter="AdminBulkImportTest|AdminSpreadsheetSafetyTest"
```
