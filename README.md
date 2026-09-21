# Learn to Drive

Learn to Drive is a driving-test preparation platform with a Laravel backend,
administration interface, learner web experience, and Flutter mobile client.

## Repository layout

```text
backend/   Laravel API, admin panel, learner website, and scheduled jobs
mobile/    Flutter mobile application
docs/      Feature documentation and operational guides
```

The repository has one Git root. Run Laravel, Composer, npm, and PHP commands
from `backend/`; run Flutter commands from `mobile/`.

## Prerequisites

- PHP 8.2 or later with the required database, ZIP, and GD extensions
- Composer
- Node.js and npm
- MySQL or another database supported by the configured Laravel connection
- Flutter 3.7.0 / Dart 2.19.0 for the current mobile dependency lockfile

## Backend setup

From a fresh checkout:

```powershell
cd backend
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Configure the database, mail, storage, authentication, and other services in
`backend/.env`, then run migrations and start the development server:

```powershell
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

Build frontend assets in a second terminal from `backend/`:

```powershell
npm ci
npm run build
```

The application is then available at `http://localhost:8000`.

## Mobile setup

Start the backend first, then from `mobile/`:

```powershell
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/
```

`10.0.2.2` points an Android emulator to the development computer. For a
physical device, use the computer's LAN address and ensure both devices can
reach the backend. Use HTTPS and production configuration for release builds.
See [mobile setup](mobile/README.md) and [integration status](mobile/IMPORT.md)
for current compatibility and API notes.

## Verification

Backend checks:

```powershell
cd backend
php artisan test
npm run build
git diff --check
```

Mobile checks:

```powershell
cd mobile
flutter analyze --no-pub
flutter test --no-pub
```

## Documentation

Feature and operational guides are available in [docs/](docs/). Open
[learn-to-drive.code-workspace](learn-to-drive.code-workspace) in VS Code to
work with the backend, mobile client, and documentation together.

## Security

Never commit `.env` files, application keys, database credentials, payment
secrets, or provider tokens. Client applications and compiled assets are public;
server-side validation and authorization remain mandatory for protected actions.

## License

This is a personal project and is not currently licensed for reuse or
redistribution.
