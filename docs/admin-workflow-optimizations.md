> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Admin review workflow

## Review navigation

Previous/Next Question follows the current bank and filters in stable page/number/ID order. **Save & Next** imports as Draft and opens the next matching candidate; **Reject & Next** rejects and advances. Back to Candidates retains filters and the originating list page. At the end of a filtered list, saving returns to that list. There is no automatic publishing or approval.

## Background extraction

The question-bank page now offers **Start Background Run** for English text or Nepali OCR. It accepts up to 100 pages, handles one page per job, and reports progress, empty pages and candidate counts. Existing one-page OCR and synchronous text extraction remain available.

Runs use a dedicated database queue even when the application's general queue is `sync`. Start the worker from the repository root:

```powershell
php artisan queue:work pdf-extraction --queue=pdf-extraction --sleep=2 --tries=3 --timeout=150 --memory=256
```

For a hidden Windows worker with log files under `storage/logs`:

```powershell
powershell -File scripts/start-pdf-worker.ps1
```

The worker must remain running for queued jobs to start. Restart it after changing job code or configuration; use `php artisan queue:restart` to let existing workers finish their current job and exit, then start again. Production installations should manage the command with their process supervisor. The local verification processed A/K OCR pages 5–6: Completed, zero new candidates and 16 known candidates.

Each page gets three attempts with backoff. The job timeout (150 seconds) is below the queue reservation timeout (240 seconds), following [Laravel's queue guidance](https://laravel.com/framework/docs/12.x/queues). A failed run can be retried from its unfinished page. Generation/page checks ignore stale deliveries; a run lock prevents concurrent processing. Queuing the next page and advancing progress share a database transaction. Source checksums must still match.

Cancellation stops remaining pages; an in-progress page can finish and retain candidates already extracted. Failed runs show a general error; detailed exceptions remain in server logs. One active background run per bank is permitted. Run requests and transitions are audited; queued extraction itself is attributed to System, with the requesting admin recorded on the run.

## Duplicate checks

Before import, **Possible Duplicates** shows up to eight matching Questions or Pending candidates, including candidates from other banks. Exact comparison normalizes case, punctuation, whitespace and Nepali digits. An existing Question with the same normalized wording, including trash, blocks import.

Similar wording is advisory. It uses token overlap and can flag questions with different meanings (especially negation). For bounded work, approximate matching considers up to 200 recent entries per record type sharing one of three longer words; it is not an exhaustive semantic search. Exact matching uses an indexed hash and is not limited by that approximate-match pool. Existing duplicates are not deleted or merged.

## Dashboard and performance

The dashboard shows Pending reviews, missing answers, diagram checks and OCR reviews, with bank links and recent queued/running/failed extraction runs.

Derived review flags avoid parsing JSON or scanning warning text for each filter. Indexes cover bank/status/page ordering, exact question matching, workload counts and signup dates. Candidate lists omit raw extracted text and warnings until the detail page. Signup counts use indexed date ranges. Analytics streams only needed attempt columns instead of hydrating the entire attempts table into memory; scoring semantics remain unchanged.

Local SQLite `EXPLAIN QUERY PLAN` confirmed covering-index use for the bank/status review list and signup-date count. No timing claims are made for larger production datasets. Derived fields update on normal model saves; future raw SQL/bulk writers must keep them synchronized, as the text extractor does.
