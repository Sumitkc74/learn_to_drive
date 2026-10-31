# Database and consistency audit — 2026-09-28

Verification: the final backend suite passed **295 tests / 2,133 assertions**;
Flutter analysis reported no issues and all **14 Flutter tests** passed. The
active SQLite audit found **0 candidate duplicate groups across 15 tables** and
0 inconsistent booking claims. No application records were deleted. SQLite
EXPLAIN confirmed the new covering indexes for staff lookup, vehicle schedules
and lesson booking status. Index migration applied locally.

## Scope and observed state

The workspace has one application SQLite database (`backend/database/database.sqlite`).
Other discovered `.db` files belong to generated browser profiles/caches, not a
second learner data store. Remote hosting databases were not accessed. The
duplicate audit checks candidate business keys rather than treating repeated
IDs across different tables as duplicates.

The read-only audit covers accounts, content, schools, memberships, cars, lesson
slots, booking claims, payment transaction references and bookmarks. It emits
aggregate counts without exposing names, email addresses or credential values.
Null active booking claims and null payment references are not duplicate keys.
Content comparisons use database LOWER/TRIM (Unicode case folding depends on
database/collation). Matching titles can be legitimate revisions, not records
safe to delete. No records are automatically merged or deleted.

```powershell
cd backend
php artisan database:audit-duplicates
php artisan database:audit-duplicates --json
# Use only an intentionally configured connection you are authorized to inspect:
php artisan database:audit-duplicates --connection=mysql --json
```

This scans one selected connection at a time; it does not compare records between
independent production databases or search backup contents.

## Improvements applied

- Seven composite indexes support approved-school sorting, user-to-school staff
  lookup, instructor vehicles, lesson availability/overlap checks and booking
  lookups. Migration `2026_09_28_000014_index_school_lesson_queries` is reversible.
- Booking provider availability is selected with the booking query. The view no
  longer makes one database query for each displayed booking.
- The shared lesson `overlapping` scope supplies consistent half-open intervals:
  adjacent lessons are permitted, overlapping ones are not.
- Content/account bulk import duplicate detection uses one bounded candidate
  query per upload instead of up to 500 per-row duplicate queries. Existing names
  are trimmed consistently. School membership relation validation remains scoped
  to approved schools; imports remain capped at 500 rows.
- Duplicate-audit and batched-lookup regression tests were added.

## Querying the newer schema

| Entity | Join / lookup |
| --- | --- |
| School staff | school_memberships.user_id → users.id; driving_school_id → driving_schools.id; filter role |
| Instructor cars | lesson_vehicles.instructor_id → users.id; driving_school_id → driving_schools.id |
| Lesson schedule | driving_lessons.lesson_vehicle_id → lesson_vehicles.id; timestamps stored in UTC |
| Booking history | lesson_bookings.learner_id → users.id; driving_lesson_id → driving_lessons.id |
| Occupied slot | lesson_bookings.active_lesson_id is unique; cancelled history uses NULL |
| School map | driving_schools.status = Approved; latitude/longitude may be NULL |

Use model relations/scopes and bound query parameters. Database primary keys are
table-local: user ID 1 and school ID 1 are unrelated, not duplicate records.
Foreign keys and existing uniqueness constraints should remain enabled. Media
file paths are database references; binary uploads stay in configured storage.

## Limits and deployment follow-up

Passing tests is not a guarantee of an error-free or load-tested application.
The local database is small. Benchmark realistic row counts and concurrent
booking/import traffic before claiming production capacity. SQLite has a single
writer; production scheduling should be verified on the intended MySQL/PostgreSQL
engine, particularly row-lock behavior and unique slot claims. Application-level
title duplicate checks are not universal cross-request unique constraints.

Searches using `%term%` and normalization functions can still scan rows; switch
to indexed search appropriate to the production engine once measured traffic
justifies it. Imports use synchronous validation and writes. Bigger background
jobs require queue workers, shared cache/locks, monitoring and retry policy.
Multiple web instances also need shared sessions/storage and externalized media.

No mass renaming of existing legacy columns or deletion of historical records was
performed: those changes require compatibility migrations and explicit merge rules.
