# Student exams and licence history

Open **My school → Student exams and licences**. Search students, filter exams
by date or outcome, or select Upcoming exams. Open a student to add or edit:

- Exam attempts: category, Written/Practical/Medical/Other, exam date, centre,
  result, result date and notes. Add a separate entry for each retake.
- Previous licences: category, optional issue/expiry dates and notes.

Owners and managers edit records for current students in their approved school.
Instructors read only students in their assigned batches. Students read only
their own records. Notes are visible to students. These are school-maintained
records, not government verification; passing does not create a licence.
An empty licence history means not recorded, rather than no licence held.
Dates use the Gregorian calendar (AD).

Duplicate attempts/categories and stale updates are rejected. Changes write audit
metadata without copying notes. Membership removal hides records but retains them;
user or school deletion cascades to records. Records are separate for each school.

Flutter: **Account → School centre → Open school → Student exams and licences**.
Authorized records are displayed natively; management uses the secure browser
handoff. English and Nepali labels are available.

Authenticated shared API:

- `GET /api/school-centre/{school}/students`: roster and exam schedule. Filters:
  `q`, `result`, `date_from`, `date_to`; pagination: `page`, `exam_page`.
- `GET /api/school-centre/{school}/students/{student}`: exams, licences, `canEdit`.
- `POST /api/school-centre/{school}/students/{student}/exam` or `/licence`:
  create; include `id` and `version` to edit.

Run `php artisan migrate` from `backend/`. No student records are seeded.
Backend tests: `php vendor/phpunit/phpunit/phpunit --filter=SchoolStudentRecord`.
Mobile test: `flutter test test/student_records_test.dart` from `mobile/`.
