> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Content readiness and system health

The main Dashboard has two new panels. **Content Readiness** links to `/admin/content-readiness`, where admins can filter draft questions and open their editor.

Readiness checks cover:

- Incomplete answer data: empty wording/options, an invalid answer letter, or duplicate options.
- Answers without an explicit verification record.
- Missing images for Road Signs questions or imported questions flagged as diagram-dependent. These are prompts for inspection, not proof that every matched question requires an image.
- Missing explanations. Explanations remain recommended, not a new publishing requirement.

Published, archived and soft-deleted questions are excluded. Counts overlap. The dashboard does not publish questions or change publishing validation.

**Verify Answer** requires confirmation and the exact question snapshot shown to the admin. A concurrent wording, option, answer, or image change blocks the stale submission. Changes to those fields, or adding/removing/updating question images through the media library, clear verification. Explanation-only edits retain it. The authenticated admin and verification time are recorded and audited. New PDF imports carry verification because their existing import form requires an explicit answer check. Existing drafts are not retroactively assumed to be verified.

**System Health** reports:

- PDF-worker heartbeat: the dedicated worker records its presence at most every 30 seconds. A heartbeat within 180 seconds is recent; older retained heartbeats are stale. No recorded heartbeat means availability is unknown, not necessarily that a worker is down. Heartbeats expire from cache after 10 minutes. Restart older workers to load the new tracking code.
- Queue backlog, failed extraction runs, and active runs with no recorded progress for five minutes. A long wait is an attention signal, not proof of a dead worker.
- Whether private storage (or its parent before creation) is writable; free/total space on its volume; tracked source-download bytes. Download totals exclude previews, OCR packages/models and logs, and do not constitute a full filesystem usage scan.
- The latest successful or failed discovery request for each configured official source. **Check Source** and `content:fetch-official` record results. A PDF download and the read-only preview command are separate operations. Previous check history is not fabricated; sources start as Never checked. Errors are logged on the server; the dashboard exposes no exception text.

Dashboard requests read local data only and perform no external source checks, OCR or recursive filesystem scans. Values refresh when the dashboard is reloaded. Additional indexes support readiness and stale-run queries.

To refresh workers after this change, use `php artisan queue:restart`, then start the dedicated worker as documented in [admin workflow optimizations](admin-workflow-optimizations.md).
