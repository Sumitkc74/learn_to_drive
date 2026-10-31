# Local demo preview

Explicitly run from `backend/`:

```powershell
php artisan db:seed --class=DemoPreviewSeeder
```

Only local/testing environments are accepted. This seeder is not called by the
default DatabaseSeeder. Existing records and edits are preserved on repeat runs.
Demo records are labelled `[DEMO]`; linked school rows belong to demo accounts.

Six login accounts are created at `demo.{role}@example.test`, with roles `owner`,
`manager`, `instructor`, `student`, `student2` and `premium`. Their generated
password is in `backend/storage/app/private/demo-preview-accounts.json`. The file
is private and must not be committed or deployed. Existing demo passwords are
not reset by rerunning the seeder.

- Existing admin: inspect users, schools, content, support and school payments.
- Demo owner/manager: School centre → approved demo school → classes and records.
- Demo instructor: My school → My vehicles and lesson times.
- Demo student: My driving bookings, attendance and exam/licence records.
- Demo premium: preview the Premium interface; live chatbot still requires Gemini.

Includes an approved fictional Kathmandu school and pending fictional Pokhara
school, two cars, six lesson slots (including a matching-price reschedule option), bookings, an entirely simulated paid receipt,
a course/batch, three classes, attendance, exam attempts, previous categories,
notifications, support requests, notices, draft questions, tutorials and sample
PDF documents. Existing traffic-sign/vision images and practice questions remain.
Tutorial links lead to local practice rather than pretending to be real videos.

No money is collected, emails sent, official notices fabricated, or accounts
granted platform administrator access. Coordinates represent demonstration
locations, not real businesses. The invoice gateway `demo` cannot initiate a real
payment. Do not deploy this local database as production data.

For the optional non-destructive baseline reset, see [School learning tools](school-learning-tools.md).
