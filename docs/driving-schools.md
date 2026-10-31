# Driving school implementation

## Public school map

Learner navigation → Find schools (`/learn/schools`) is available to guests and
signed-in users. Search approved schools by name/address. Each page lists up to
24 schools; its map contains only those results with saved coordinates. Schools
without coordinates remain in the list. School names, addresses and phone numbers
are public; emails, owners and memberships are not included in this directory.

In Admin → Driving Schools → Manage school, enter latitude and longitude together
and save. Clear both to remove a map marker. Only approved schools are published.
Coordinates must be verified by the admin; no sample locations or automatic
geocoding are used. Existing schools start without coordinates.

The web map uses [Leaflet 1.9.4](https://leafletjs.com/examples/quick-start/) loaded
with integrity checks, and attributed OpenStreetMap tiles. The list and external
directions links remain available if external map services cannot load. No API
key or automatic user-location permission is needed. For production traffic,
follow the [OSM tile policy](https://operations.osmfoundation.org/policies/tiles/);
public tiles are best-effort, not a guaranteed hosted service. Native Flutter map
integration is not included in this web feature.

## School partner workflows (October 2026)

Account → **School centre** is available to all learners on web and Flutter.

1. A learner submits a school application with its contact details. The account
   becomes its Owner, but the school stays Pending. Admin → People → Driving
   Schools reviews the application and approves or suspends it. Contact and
   instructor credentials must be checked by the administrator; submission is
   not automatic verification.
2. Owners invite existing active accounts as Manager, Instructor or Student.
   Managers invite Instructors and Students. Invitations expire after seven days;
   the recipient must accept before membership is created. Invitations cannot
   silently change an existing member's role.
3. Owners explicitly change roles or transfer ownership to an existing Manager.
   Staff access is always scoped to approved schools. Removing membership does
   not delete the person's account or personal learning history.
4. Managers create courses and dated, capacity-limited batches with an eligible
   instructor. Students join batches in their school centre. Closing enrollment
   stops new joins; existing classes can still be scheduled.
5. Managers or the assigned instructor schedule classes in Nepal time. Choose
   one occurrence or a weekly series of up to 12 occurrences. The entire series
   is rejected if any date is outside the batch or conflicts with a booking or
   class. Storage uses UTC. Class cancellation notifies enrolled learners.
6. After a class starts, its instructor or school manager records Present,
   Absent or Excused. Students see only their own attendance. Staff cannot change
   another school's records.
7. Students explicitly opt in to sharing their completed-practice count. No
   answers, chatbot conversations or individual test records are shared. They
   can withdraw consent at any time.

School centre contains in-app notifications for invitations, class changes,
bookings and payment/refund events. No email, push or SMS delivery is configured.
Class history and notification history are paginated.

## Mobile and web

Flutter Account → School centre has native school memberships, applications,
invitation acceptance, batch enrollment, class/attendance viewing, notifications
and progress privacy controls. Staff management, the map, private lesson booking,
and gateway checkout open the responsive web interface using the existing
short-lived, single-use browser handoff. The same account and language are
preserved; the native screen refreshes when the app resumes. These management
and checkout screens are browser-assisted, not native Flutter forms.

The shared `/api/school-centre` endpoints require an active authenticated account.
They reuse web authorization and workflow code. School roles never grant Admin
or Premium platform privileges. English and Nepali labels are included.

## Lesson payments and refunds

See [private lessons](private-driving-lessons.md). Online lesson payment uses a
separate invoice ledger and never grants Premium access. The platform's configured
merchant collects payment; automatic school payouts/commission settlement are
not implemented. Arrange school settlements outside this application.

## Setup and verification

From `backend/` run `php artisan migrate`. This creates the school, lesson,
invitation, course, batch, attendance, notification and payment tables. No demo
users, schools or bookings are inserted. Restart Flutter to load the new Account
menu entry. No new package or map API key is required.

```powershell
php -d extension=gd -d extension=zip vendor/phpunit/phpunit/phpunit --filter="School|PrivateDrivingLesson"
```

Tests cover approval, school and role boundaries, invitation acceptance and
expiry, capacity, recurring-schedule rollback, attendance ownership, opt-in
progress privacy, bookings and payment/refund handling. Flutter tests cover
invitation acceptance and Nepal time display. Merchant verification is tested
with fake responses; live payments and production database concurrency still
require deployment verification.
