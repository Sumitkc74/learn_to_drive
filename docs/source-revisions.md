> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Source file revisions

At 03:00 Asia/Kathmandu the scheduler queues up to 20 approved official source
files that have not been checked in the past day, oldest checks first. Paused sources
are excluded. Custom websites and locally uploaded PDFs are not automatically fetched.
The existing downloader enforces HTTPS/allowlist, MIME and file-size limits.

A SHA-256 comparison detects changes to file bytes, even when the URL stays the same.
Changed files become new Pending learning resources linked to the original. They
enter AI screening and human review. Repeated identical changes do not create more
records. A latest approved revision becomes the next comparison baseline.

The original review page shows the check result and revision links, and provides a
manual Check for updated source file button. Dashboard alerts count sources needing
re-review. Originals and published items are not silently replaced. After approving
a revision, an admin must explicitly update or retire the associated learner content.

Byte changes may be cosmetic; a human must decide whether an update matters. Checks
do not detect removal of a link from a listing, and failed downloads do not imply
that the original is invalid. The worker and scheduler must be running.
