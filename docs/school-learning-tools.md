# School learning tools

## Booking changes and waitlists

From **My driving bookings**, reschedule a future booking to an available slot at
the same school and price. The booking and invoice identities remain unchanged;
slot changes are recorded in `lesson_booking_changes`. Availability and conflicts
with private lessons and school classes are checked again when saving.

From **Private driving lessons**, choose **Booked — join waitlist** to request an
in-app availability alert. Cancellation or rescheduling notifies waiting users.
An alert is not a reservation: the first successful booking gets the slot. An
instructor cancelling the entire slot sends a cancellation notice instead.
Flutter opens these booking tools through the existing authenticated web handoff.

## Student feedback and reports

School centre → student records → **Readiness and instructor feedback** shows
attendance, upcoming exams and instructor feedback. Owners/managers and the
assigned batch instructor can record a skill assessment and next-lesson notes
after a completed class with Present attendance. Re-entering the same skill for
the same class updates its assessment. Students can read their own feedback.
This feedback currently covers school classes, not private lesson bookings.

Completed practice totals are visible to the student; staff require the existing
progress-sharing consent. There is no invented readiness percentage or official
licence eligibility claim. Owners/managers have date-filtered school reports and
web CSV export, with a maximum 366-day interval. Reports use current record
statuses, activity dates, and invoice creation dates. Test payments are separate
from live payments; these summaries are not an accounting ledger.

Flutter provides native exam/licence creation and editing, result/type selectors,
date entry/calendar buttons, readiness, feedback and report filters. Record IDs
and versions are preserved for the existing stale-update protection. Labels use
the shared English/Nepali dictionary.

## Reminders

`php artisan schools:send-reminders` sends in-app reminders for exams today or
tomorrow, upcoming private lessons within 24 hours, and overdue unrecorded exam
results to school managers. A database delivery key suppresses repeat messages.
Suspended schools and inactive recipients are excluded. No email or push service
is implied. Laravel schedules the command hourly; the hosting server must run
`php artisan schedule:run` every minute (or `schedule:work` during local testing).

## Local preview reset

After migration, run `php artisan db:seed --class=DemoPreviewSeeder`. The first
local run captures a private baseline in
`backend/storage/app/private/demo-preview-baseline.json`. Later seeder runs
preserve that baseline and existing edits.

Run `php artisan demo:reset` to check eligibility without changes; use
`php artisan demo:reset --apply` to restore the captured school-related rows.
Only local/testing environments are allowed. Reset refuses schools with real
members, real learner bookings/provider payments, or changed record ownership.
It neither deletes new records nor recreates deleted records, resets passwords,
touches media, nor restores invoices. Conflicting restores roll back atomically.
Keep both baseline and demo credentials private and out of Git.
