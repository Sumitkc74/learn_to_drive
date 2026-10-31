# Private driving lessons (web)

## Start using it

1. Admin creates and approves a school. Its owner/manager invites an existing active
   account with the school role **Instructor**. The instructor accepts in School centre.
2. Instructor signs in → My school → My vehicles and lesson times. Add a car,
   registration and Manual/Automatic transmission. Registration is private and
   unique across vehicles; each vehicle has one instructor and school.
3. Publish a start time, duration (15–180 minutes), meeting point and whole-NPR
   price. Entry and display use Asia/Kathmandu; storage uses UTC.
4. Learners open Find schools → Book a private driving lesson. Filter by date,
   transmission, instructor, car or school, then confirm a slot while signed in.
5. Account → My driving bookings shows history and upcoming bookings. Instructors
   see their bookings and learner name/email on their lesson schedule.

## Rules

- One learner per slot. A unique active-slot database constraint and row locks
  protect the claim; cancelled booking rows remain for history.
- Instructor scheduling is serialized on their user row and rejects overlaps
  across all their cars/schools. Learner booking requests are serialized on their
  user row and reject overlapping bookings. Adjacent lessons are allowed.
- Only future lessons from active instructors currently assigned to approved
  schools can be booked. Instructors cannot book their own lessons.
- Learners can cancel only their own future booking, reopening the slot.
  Instructors can cancel only their own future lesson, closing it and cancelling
  its active booking. Slots cannot be silently edited after publication.
- If a school is suspended or the instructor membership is removed, new bookings
  stop. Existing bookings display a provider-unavailable warning; learners can
  still cancel and instructors can still cancel their own upcoming lessons.
- Booking, cancellation and payment events create in-app school notifications.
  No emails/SMS/push are sent. Check your bookings before travelling.

## Online payment setup

Direct payment is the default. Optional Khalti/eSewa checkout uses the existing
`payments` merchant credentials and separate school invoices. In `backend/.env`:

```dotenv
SCHOOL_PAYMENTS_ENABLED=true
SCHOOL_PAYMENTS_LIVE=false
```

Use the appropriate test merchant credentials for the existing `KHALTI_SECRET_KEY`,
`ESEWA_PRODUCT_CODE` and `ESEWA_SECRET_KEY` settings (see `backend/config/payments.php`
for the authoritative environment names). Run `php artisan config:clear` after
changing configuration. Test mode is refused in production. Enable live mode
only after configuring a live merchant, HTTPS callbacks, and completing sandbox
tests. No keys are exposed to Flutter or browser JavaScript.

Learner → My driving bookings → Pay online → receipt → Continue to payment
provider. After returning, select Verify payment. Merely loading a callback/receipt
does not mark an invoice Paid. The server checks the provider's status, invoice
identifier and exact amount. Repeated verification cannot double-credit an
invoice, and school payments cannot activate Premium. Prices below NPR 10 use
direct payment only. Never collect both direct and online payment for one lesson.

Cancelling before a lesson starts requests a full refund of verified online
payment. A payment verified after cancellation also becomes RefundRequested.
Admin → Driving Schools → School payments and refunds lists these invoices.
Complete the refund through the merchant first, then record its unique reference
and confirmation. Recording a reference does not move money. Refunds are manual,
not instant; no automatic provider refund or school payout API is implemented.

Official integration references: [Khalti ePayment](https://docs.khalti.com/khalti-epayment/)
and [eSewa ePay](https://developer.esewa.com.np/pages/Epay).

## Scope and validation

Instructors must belong to an approved school. Vehicle registration and instructor
eligibility are entered by staff and reviewed operationally. Group-class weekly
series are supported through School centre; private lesson slots remain individual.
Flutter opens booking and checkout through a secure browser handoff.

Run `php artisan migrate` from `backend/`. Tests:
`php -d extension=gd -d extension=zip vendor/phpunit/phpunit/phpunit --filter="PrivateDrivingLesson|SchoolLessonPayment"`.
These are isolated database and mocked gateway regressions; live merchant flows
and production concurrency/load testing remain deployment checks.
