# Mobile security review

The Flutter client was reviewed for session handling, credential transport,
local storage, private-data logging, and Android release defaults.

## Implemented safeguards

- Tokens and profile data are memory-only. Startup removes legacy GetStorage
  token/profile values; local profile records cannot establish authentication.
- Logout clears tokens, the profile, attempted questions, and registered
  controllers even if server logout fails. Failed server revocation is reported
  honestly: it does not mean the remote token was revoked.
- Protected API responses with status 401 clear the local session and return to
  login. Responses arriving after an account changes are rejected.
- All repository API calls use one client, restricted to the configured API
  origin and path. It refuses URL credentials, disables redirects, strips old
  bearer tokens from login/registration/reset requests, and requires HTTPS in
  profile/release builds. HTTP is allowed only in debug for local development.
- Response headers and bodies have separate 20-second time limits. Auth failures
  use safe fallback messages instead of exposing transport exceptions or HTML.
- Removed sensitive local-storage/payment logging and the obsolete direct payment
  request. Payments remain on the website's server-verified checkout.
- Password fields disable keyboard suggestions and autocorrection.
- Android backups are disabled. Release builds no longer use debug signing keys;
  configure private release signing before distributing an APK or app bundle.

## Verification and limits

### API and network follow-up

- API password changes keep the current token and revoke other API tokens.
- Login: 5 requests/minute per normalized account, 20/minute per IP.
  Registration: 3/minute per IP. Password change: 5/minute per user.
- The history endpoint accepts `answers: [{question_id, selected_option}]`,
  maximum 100 distinct published questions, options A-D or null. Old client-supplied
  question/answer snapshots are no longer accepted as the source of truth.
  The server calculates the score and saves its own question/answer snapshot.
  This is practice history, not a proctored exam: published learning questions
  still expose their answers. The updated mobile client uses the new contract.
- History is paginated at 20 entries; the mobile list has a Load more action.
- Mobile API responses are limited to 5 MiB; PDFs to 20 MiB, including chunked
  responses. The reader cancels oversized streams. PDF previews download only
  from the configured backend origin, refuse redirects and require a PDF header.
- Browser links allow the configured backend, DoTM and its subdomains, and
  youtube.com/www.youtube.com/youtu.be over HTTPS. Unknown hosts, credentials in
  URLs and non-web schemes are blocked. Add new reviewed destinations explicitly
  in `SafeLinks`; do not disable validation. Browser navigation after opening a
  trusted external site is controlled by that browser, not the mobile API client.

### Hosting configuration

Forwarded headers are now trusted only from explicit proxy addresses. On the
hosting server set `TRUSTED_PROXIES` to the actual proxy IPs/CIDRs (comma separated).
Leave it empty when requests reach Laravel directly. Do not set it to `*`.
The proxy must sanitize forwarded headers; firewall the origin appropriately.

Use `APP_ENV=production`, `APP_DEBUG=false`, an HTTPS `APP_URL`, and
`SESSION_SECURE_COOKIE=true` on production. Rebuild Laravel's configuration cache,
then run `php artisan security:check-network` from `backend/`. This command prints
pass/fail checks without secrets and exits nonzero on unsafe production settings.
Local development intentionally remains configured for HTTP/debug. A successful
configuration check does not verify the hosting server's TLS certificate, firewall,
or proxy configuration; those still need a deployment check.

Run `flutter test` and `dart analyze` from `mobile/`. Regression tests cover
origin/path/HTTPS enforcement, redirect policy, public authentication headers,
session clearing, and responses from a previous account.

This is code hardening, not a penetration-test certification. No Android/iOS
device build, live account or payment test was performed. Flutter 3.7 and its
dependencies still need a planned upgrade before production distribution.
Mobile MFA and shared learner features are documented in `learner-feature-parity.md`.
Login tokens remain memory-only. Server authorization and premium access
must remain enforced by Laravel; client role checks are not a security boundary.
