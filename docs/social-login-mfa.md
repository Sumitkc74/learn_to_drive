> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Google sign-in and optional MFA

## Google setup

Create a Google OAuth client of type **Web application** in your Google Cloud project. Configure the consent screen and add test users while the app is in testing. Follow [Google's setup documentation](https://developers.google.com/identity/openid-connect/openid-connect).

Add this exact authorized redirect URI for local development:

`http://127.0.0.1:8000/learn/auth/google/callback`

Use your real HTTPS domain with the same path in production. The browser hostname, APP_URL, and redirect URI must agree; localhost and 127.0.0.1 are different hosts.

Set these values in your local `.env` (never commit credentials):

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/learn/auth/google/callback
```

Then run `php artisan config:clear`. The Google button appears on learner login and registration once all three values are configured. No other provider is enabled.

New Google users finish registration with a name, phone number and backup password. Existing users must sign in with their password and explicitly connect Google under Account settings. Matching email addresses do not automatically link accounts. A Google callback is validated through Laravel Socialite's session state and provider response; provider access tokens are not stored.

Live Google sign-in requires your OAuth credentials and a browser round trip. Automated tests mock the provider and do not establish that your Google project is configured correctly.

## Authenticator MFA

Go to Account → Account settings → Two-step verification. Confirm your password, add the displayed setup key to a compatible authenticator app as a **time-based** account, and enter its six-digit code. Setup expires in 10 minutes. No external QR service receives the secret.

Save the eight recovery codes displayed after activation. Each works once. Secrets are encrypted with Laravel's APP_KEY, and recovery codes are stored as hashes inside encrypted storage. Keep APP_KEY securely backed up. The app rejects reused authenticator time steps, so wait for the next code if one was just used.

MFA applies to password and Google web sign-in, including admin accounts that enable it. Existing web sessions without MFA confirmation are challenged. Enabling MFA revokes existing API tokens; password-only API login is blocked for MFA-enabled accounts until the future app supports MFA. Password reset does not disable MFA.

To disable MFA, provide your password plus a fresh authenticator code or unused recovery code. To replace recovery codes or your authenticator, disable and set up MFA again. If both the authenticator and all recovery codes are lost, there is no self-service bypass; an administrator must perform identity-verified recovery outside this flow.

## Deployment and verification

Run `composer install`, `php artisan migrate`, and `php artisan config:clear`. Run `php artisan test --filter=SocialMfaTest` for the focused security checks. MFA uses your server clock; keep it synchronized.
