# Demo deployment: Render (free web) + Aiven (free MySQL)

This is the $0 demo setup for Learn to Drive. It is a demo, not production:
the free web service sleeps after 15 minutes idle, uploads live on an
ephemeral filesystem, scheduled jobs do not run (no free cron/worker), and
email verification links go to the app log instead of an inbox.

## 1. Create the free MySQL database (Aiven)

1. Sign up at Aiven (no credit card for the free tier) and create a
   **MySQL free tier** service: 1 CPU, 1 GB RAM, 1 GB storage, single node.
2. Note its host, port, database name, username, and password. Aiven may
   power off a free service after a period of inactivity, with advance
   notice; power it back on in the Aiven console if that happens.
3. Do not use Render's free PostgreSQL for this demo: it expires after
   30 days. The app's default database is MySQL (`DB_CONNECTION=mysql`
   in `backend/config/database.php`), so Aiven needs no code change.

## 2. Create the Render web service

1. In Render, create a **Web Service** from this repo using the Blueprint
   in `render.yaml` at the repo root, or create it manually with:
   - Environment: Docker, root directory `backend/`, Dockerfile
     `Dockerfile` (in the dashboard, the Dockerfile path is resolved
     relative to the root directory), plan **Free**.
   - Note: in `render.yaml`, `dockerfilePath` and `dockerContext`
     are resolved relative to `rootDir`, not the repo root, in
     practice — Render combined `rootDir: backend` with
     `dockerContext: ./backend` into `backend/backend` and failed.
     So the Blueprint keeps `rootDir: backend` with
     `dockerfilePath: Dockerfile` and `dockerContext: .`. Do not
     re-add the `backend/` prefix to those two fields.
2. Set these environment variables in the Render dashboard (never in the
   repo):
   - `APP_KEY`: generate locally with `php artisan key:generate --show`
     (run it in `backend/` with PHP installed) and paste the result.
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`:
     the Aiven values from step 1. Aiven's port is usually not 3306;
     use the exact port Aiven shows.
   - `SEED_ADMIN_PASSWORD`: a strong password for the first admin
     account. The entrypoint runs migrations and then `db:seed`, which
     creates `admin@admin.com` with this password (and does nothing if
     that admin already exists). Change the admin password after your
     first login; the seeder will not overwrite an existing admin.
   - `GEMINI_API_KEY`: your Google Gemini API key. Required for
     government-notice AI screening and admin AI chat. Without it,
     screening jobs fail and freshly fetched government notices can
     never be approved, because approve requires a Ready or Flagged
     screening. The entrypoint also starts a background worker for the
     `pdf-extraction` queue in the same container (Render free has no
     worker service), so no separate worker service is needed — but
     the worker can only complete screening once this key is set.
   - `APP_URL`: after Render assigns the real `.onrender.com` URL,
     set this to exactly that URL (https) and redeploy, otherwise
     asset and link URLs will be wrong. The placeholder in
     `render.yaml` must be replaced with the real URL.
   - The rest (`APP_ENV=production`, `APP_DEBUG=false`,
     `SESSION_DRIVER=cookie`, `CACHE_DRIVER=file`,
     `QUEUE_CONNECTION=sync`, `MAIL_MAILER=log`,
     `TRUSTED_PROXIES=*`) are already in `render.yaml`.
3. Deploy. The container entrypoint (`backend/docker/entrypoint.sh`)
   binds Apache to Render's `$PORT`, caches config, runs
   `php artisan migrate --force`, seeds, and links storage, then serves
   the app from `backend/public/`. It also runs
   `php artisan db:seed --class=RealContentSeeder --force` on every boot,
   which populates the real learning content (questions, tutorials, exam
   information). That seeder is idempotent, so re-runs never duplicate
   rows; it is deliberately not wired into `DatabaseSeeder`, because the
   test suite seeds that for every test and assumes an admin-only baseline.

## 3. Verify

- Open the Render URL: the learner site should load (first visit after
  idle takes about a minute while the free service wakes).
- Log in as `admin@admin.com` with the `SEED_ADMIN_PASSWORD` you set.
- Public registration requires email verification, and with
  `MAIL_MAILER=log` those links land in the Render logs, so demo
  visitors cannot self-verify by email. For a demo, create accounts via
  the admin account, or change the mailer later to a real provider.

## 4. Known free-tier limits

- Sleep: free web services spin down after 15 minutes without traffic.
- Uploads: the filesystem is ephemeral; files uploaded through the app
  do not reliably survive a restart or spin-down.
- Scheduler: no free cron/worker on Render, so scheduled jobs in the app
  do not run on this tier.
- Payments: payment upgrades stay disabled until server-side payment
  verification is implemented (see `backend/README.md`); do not enable
  real payments on this demo.
- Aiven free MySQL: 1 GB storage, no SLA, and it may power off when
  idle (see step 1).

## 5. What is already in the repo for this

- `backend/Dockerfile`: Node 22 stage builds Vite assets, then
  PHP 8.3 + Apache serves Laravel from `public/`, with the extensions
  the app and its packages need (pdo_mysql, mbstring, zip, gd, exif,
  bcmath, intl, xml, curl, fileinfo).
- `backend/docker/entrypoint.sh`: binds Apache to `$PORT`, caches
  config, migrates, seeds, links storage.
- `backend/.dockerignore`: keeps `.env`, `vendor/`, `node_modules/`,
  and local build output out of the image.
- `render.yaml`: the Blueprint above, with all secrets marked
  `sync: false` so they are set in the dashboard, never committed.
