> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Scraping, AI screening, human review

New learning content and government notice imports queue Gemini screening automatically.
Gemini uses the existing GEMINI_API_KEY and GEMINI_TRANSLATION_MODEL settings.
Provider usage may incur charges under your Gemini plan.

Learning content screening downloads a private PDF/image through the existing safe
downloader and sends its bytes to Gemini (maximum 8 MB for AI screening). Notice
screening sends up to 18,000 characters from the linked page; attached documents
are not screened. AI reports show the scope, summary, and issues. They are advice,
not proof of accuracy or authenticity.

Queued/Running/Failed imports cannot be approved. Ready/Flagged imports require a
human to inspect the original and make the decision. Rejection is always available.
Changed learning file hashes or source metadata invalidate screening. Existing
older imports have no mandatory AI state; Run / retry AI screening opts them in.
AI never publishes content. Government notice approval still creates only a draft.

Daily official-source checks run at 02:00 Asia/Kathmandu for notices and 02:10 for
learning content. Existing URL deduplication prevents repeated suggestions. These
checks cover configured official sources, not arbitrary previously entered websites.

On Windows, scripts/run-content-scheduler.ps1 runs schedule:run and drains the
existing pdf-extraction queue. Register it as a recurring one-minute Windows task.
The computer must be awake and the user signed in for an interactive-user task.
Logs: storage/logs/content-scheduler.log. The queue also serves PDF translation and
extraction. On a server, run Laravel's scheduler each minute and a persistent worker:

    php artisan queue:work pdf-extraction --queue=pdf-extraction --timeout=150 --tries=1

CONTENT_AI_SCREENING defaults to true. Tests disable automatic dispatch unless
explicitly testing screening. Failed/stuck items can be retried on their review page.

Local Windows setup uses a persistent trusted CA bundle at
`storage/app/private/source-ca.pem` (configured by `OFFICIAL_CONTENT_CA_BUNDLE`),
and PHP extension overrides in `storage/app/private/php-scheduler`.
These local files must remain available; do not point unattended jobs at Windows Temp.

Automatic recovery runs every five minutes. Pending imports in Queued, Running, or
Failed state with no AI activity for ten minutes are requeued, at most three times.
After the limit they remain failed for manual investigation. A manual retry resets
the automatic retry budget. Recovery does not approve, publish, or rescreen Ready or
Flagged results. It also recovers queued jobs lost when a worker stopped.

Import History in the sidebar records configured official source checks, results,
new/known counts and safe failure details from now on. The dashboard highlights latest
source failures, failed AI checks and notices expiring within seven days.

Learning resource review displays the saved original beside AI findings and review
controls. Identical saved file hashes are linked across source URLs; screening flags
exact duplicates without calling Gemini. No file is deleted automatically.

CONTENT_AI_DAILY_LIMIT defaults to 100 screening requests per Nepal calendar day.
Set it to 0 to pause provider calls. Usage reservations are atomic and include failed
provider requests. At the cap, items become Deferred; recovery resumes them the next
day without consuming failure retries. Reported token totals are not a bill estimate.
This limit applies only to screening, not the separate translation features.
