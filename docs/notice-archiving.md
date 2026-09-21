> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Notice archiving

The scheduler runs `notices:archive-old` daily at 02:20 Asia/Kathmandu.
Published notices are archived when their expiry date passes. Without an expiry
date, the default is 90 days after publish_at (created_at for legacy records).
Set NOTICE_ARCHIVE_AFTER_DAYS to change that fallback period.

Future scheduled notices, drafts, trash, and notices with a future explicit expiry
are not automatically archived. A new publication with no date now records its
publication time so an old draft does not immediately age out.

Current notices excludes the archive by default. Archived notices opens the archive
filter. Manual Archive hides a notice from learners without deleting it. Restore as
draft clears its previous publication/expiry dates for human review before republication.
Status changes retain the existing audit history. Source import records remain intact
to prevent the scraper from repeatedly importing an archived notice.

Preview with `php artisan notices:archive-old --dry-run`.
