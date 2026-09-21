> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Content workflow tools

## Version history

Content history links appear beside creator information for questions, notices,
question banks, exam information, signs, vision tests, and tutorials. Text and
settings are captured on creation and changes; the first subsequent edit to a
legacy record also captures its previous state. History starts with this feature,
not retroactively. The comparison shows each saved field next to the current value.

Restore requires confirmation and an unchanged current-record hash. Questions and
notices restore as drafts. Question answer verification is cleared. Other content
types do not have a draft lifecycle, so restoring their text updates live content
after confirmation. Files are not versioned; current attachments remain unchanged.

## Bulk review and assignments

Review Operations lists pending learning resources, pending government imports,
and published notices. Select up to 25 rows for rejection, category assignment,
admin assignment, claiming/releasing review, or notice archiving. Supported actions
depend on the selected queue. Rejection needs a reason; every batch needs confirmation.
The batch is atomic: stale or conflicting items reject the whole operation.
There is no bulk publishing. Assignments prevent another admin approving/rejecting
or claiming the item; an admin can explicitly reassign it via Review Operations.

## Source management

Scraping Sources enables/pauses configured official sources and sets each daily time
in Asia/Kathmandu. Both scheduled and manual fetching respect pause state. Existing
imports remain untouched. Arbitrary new URLs still use the separate Website Import
workflow and are not automatically scheduled. Import History shows previous checks.

## Linked learner reports

Authenticated API clients can send:

    POST /api/content/{type}/{id}/report
    {"message":"Describe the problem"}

Types: question, traffic-sign, vision-test, question-bank, exam-information, notice,
tutorial. The server derives the subject, content reference, and user identity.
Questions must be published and notices must be visible to learners. Invalid/private
references are rejected. Requests are rate limited. Support tickets provide an Open
content link for admins. Existing POST /api/support also accepts these content types.

The learner frontend is outside this repository; its Report problem button must call
the above endpoint. The admin workflow and API are implemented here.

## Backups

See [Backup and restore](backup-restore.md) for coverage and the recovery procedure.
