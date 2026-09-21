> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Private review files and storage checks

Scraped PDFs and images remain on the private learning-content disk and are served
only through authenticated admin routes. Question images now use protected-media,
rooted at storage/app/private/media, including published question images.

Draft, archived and trashed question images require an administrator session.
Published images use /media/published/{media}; each request checks the owner's current
status. Responses use private/no-store caching. Changing status immediately revokes
future learner requests; previously downloaded copies cannot be recalled.

Run `php artisan media:protect-questions` to migrate legacy public question images.
The command preserves IDs, copies and verifies bytes before removing public copies.
It stops on missing originals, destination conflicts, or failed verification.
Back up the database and storage together. New uploads use the private disk automatically.
Other content types without a draft lifecycle retain their current public storage.

Open Media Library → Storage Check to run a read-only scan. It reports:

- Missing original media or private learning-resource files.
- Media whose content owner no longer exists (soft-deleted owners are retained).
- Files without a matching media directory or learning-resource file reference.
- Storage that could not be checked.

Unused-upload findings are candidates for investigation, not deletion instructions.
Generated variants inside known media directories are retained; backups and temporary
processing folders are outside the scan. The last report is stored privately at
storage/app/private/storage-check.json and is visible only to administrators.
