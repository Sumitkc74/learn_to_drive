# Learn to Drive backend

Laravel backend, administration interface, learner website, and scheduled jobs
for the Learn to Drive platform. Shared guides are in `../docs/`.

# Learn to Drive

Learn to Drive is a web application that helps users prepare for their driving test. It's built with Laravel as the backend/API, with a separate frontend for the user-facing experience.

## Current Status

🚧 **In Development**

- ✅ Admin panel (web) — manage questions, content, and users
- Flutter mobile client imported under `mobile/`; API modernization remains in progress

## Features

### Admin (available now)
- Manage driving test questions and answer options
- Organize content into categories/topics
- Manage users

### Planned (client-side)
- Browse and study driving test material
- Take practice quizzes/mock tests
- Track progress and scores

## Tech Stack

- **Backend:** Laravel (PHP) — REST API
- **Frontend:** Separate JS application (built with Vite)
- **Database:** MySQL

## Getting Started

### Prerequisites

- PHP >= 8.2 with ZIP (Excel), GD (images), and the PDO driver for your database
- Composer
- Node.js 22.12+ and npm
- MySQL

### Installation

1. Clone the repository
```bash
   git clone https://github.com/Sumitkc74/learn_to_drive.git
   cd learn_to_drive
```

2. Install PHP dependencies
```bash
   cd backend
   composer install
```

3. Install JS dependencies
```bash
   npm ci
```

4. Set up environment file
```bash
   cp .env.example .env
   php artisan key:generate
```

5. Configure your database in `.env`, then run migrations
```bash
   php artisan migrate
```

6. Build frontend assets
```bash
   npm run dev
```

7. Start the Laravel server
```bash
   php artisan serve
```

The app should now be running at `http://localhost:8000`.

## Roadmap

- [ ] Build client-facing frontend
- [ ] Practice test / quiz mode
- [ ] User progress tracking
- [ ] Mobile-friendly UI

> Payment upgrades are temporarily disabled until server-side payment-provider
> verification is implemented. Never grant premium access from a client-provided
> transaction token or amount.

## License

This is a personal project. All rights reserved — not licensed for reuse or distribution.


## Admin verification

After configuring `.env` and installing dependencies, build the frontend before testing page rendering:

```bash
npm run build
php artisan migrate
php artisan optimize:clear
php artisan test
php artisan route:list --path=admin -v
git diff --check
```

Tests use in-memory SQLite and require `pdo_sqlite`. Configure a local application key with `php artisan key:generate`. Enable ZIP to exercise Excel tests; check extensions with `php -m`.

The dashboard shows non-clickable counters, management cards, Quick Actions, Admin Tools, and full-width recent activity. Detailed charts remain on Analytics.

Question and notice imports accept CSV or XLSX, up to the saved document limit and 10,000 data rows. Duplicates are skipped. Invalid rows prevent all writes; the summary distinguishes added, skipped (blank or withheld valid rows), duplicate, and invalid rows. Blank notice status defaults to Draft. XLSX formulas and XML entities are rejected; CSV exports escape formula-like cells. Government retrieval queues suggestions for review; approval creates a draft.

Settings cards save only their own fields. Verification controls OTP expiry and minimum resend interval; the existing three-codes-per-ten-minutes cap also applies. Local phone codes are available only in local/testing. Production phone verification requires an SMS provider; email verification requires working SMTP settings.

Browser checks after deployment:

- Verify dashboard order, counters, long activity text on narrow screens, and light/dark tables.
- Open each management section, search/filter, and move between result pages.
- Import/export CSV and XLSX in Questions and Notices; retry duplicates and an invalid row.
- Save settings cards separately and check resend timing and upload limits.
- Check creator labels on tables, edit pages, question preview, and user details.
- Replace one language PDF on Exam Papers/Information and verify the other remains available.
- Confirm seed-admin restrictions, profile editing, signed email verification, and government review.

## Mobile frontend

The Flutter client is now included in [`mobile/`](../mobile/README.md), alongside
this Laravel backend and learner website. See [`mobile/IMPORT.md`](../mobile/IMPORT.md)
for the upstream snapshot and API compatibility findings. The imported client
still needs integration work for the newer learner features.
