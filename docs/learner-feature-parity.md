# Learner website and mobile app

The Flutter navigation is **My learning / Library / Practice / Account**.
New native screens live in `mobile/lib/Screens/Learner/`; the previous settings
entry point delegates to the new account screen.

| Capability | Flutter | Website |
|---|---|---|
| English/Nepali interface | Shared Nepali dictionary, local preference | Same dictionary, language menu |
| Library, search, document-language filters | Native paginated lists and details | Existing library |
| Sign/vision reveal, PDFs, tutorials, notices | Native details and secure viewer/browser links | Existing learning views |
| Saved resources and content reports | Native screens/actions | Same user-owned records |
| Practice, answer review, save/resume | Native screens and autosave | Same `LearnerPracticeAttempt` records |
| Daily progress, streak, accuracy, history | Native My learning | Same progress service |
| 30-minute mock exam, up to 25 questions | Native timer | Added setup and countdown |
| Previous practice records | Practice archive | Added owner-only archive |
| Personalized practice and revision | Native Premium screens | Same revision service |
| Premium chatbot | Native screen and floating shortcut | Same Gemini service/history/limits |
| Support tickets and replies | Native forms/conversations | Same private tickets |
| Individual account edits and passwords | Native forms | Existing settings |
| Email verification and recovery | Request from app; email links finish in browser | Existing signed verification/reset flows |
| MFA login/setup/disable and recovery codes | Native forms | Existing authenticator flow |
| Google sign-in | Secure browser approval, one-time exchange | Existing Google provider flow |
| Google connect/disconnect | Browser handoff to settings | Existing password-confirmed linking |
| Plans, upgrade request, payment history/status | Native screens | Same records and gateway verifier |
| Khalti/eSewa checkout | Browser handoff | Existing server-verified checkout |
| Dark appearance | Persistent device preference | Added persistent theme toggle |

Mobile practice includes published question illustrations. Revision includes
correct answer text, illustrations, topic groups and topic-specific retries.
Dedicated sign flashcards hide titles until revealed and reset on each card.
Expired ordinary sessions are labelled unavailable; expired mock exams offer
results instead of resume. Account/plan pages and the chatbot shortcut refresh
when the app resumes after browser checkout. Payment confirmation still comes
from the server; returning from checkout alone does not grant Premium.

## Shared behavior and security

The mobile My learning and practice screens offer **Continue on website**. A
one-use browser handoff preserves interface/practice language and the owned
practice attempt. Unsaved answers must save successfully before opening the
browser. Browser account confirmation remains required; no persistent bearer
token is included in the URL. Returning to a clean mobile practice screen reloads
the shared attempt, while unsaved local edits are retained. Approved browser
sign-in is exchanged automatically on app resume, with the manual button as a
fallback. This does not install verified Android/iOS app links or provide
persistent encrypted mobile login; those still require platform integration and
device verification. Preferences are carried with a handoff, not continuously
synchronized between independent browser/device sessions.

Practice offers English and Nepali on both clients, with the most recent choice
remembered locally. Mobile My learning uses the shared next-step recommendation,
and resource details support both saving and removing bookmarks. Website password
changes and reset links revoke existing mobile API tokens. Mobile login remains
memory-only until encrypted device credential storage is implemented.

Most new API endpoints are under `/api/learner`; support continues using the
existing support API. Published/visible content checks, ownership, active-account
checks, rate limits and Premium checks remain server-side. Practice responses
omit correct answers and explanations until submission. Expired timed mock exams
are finalized when opened again, using only answers saved before expiry; ordinary
practice retains its two-hour resume window. An incomplete mock is not counted in
progress until it is finalized.

Browser sign-in requires explicit approval of the matching short code. The app
holds a separate verifier in memory; the browser receives no bearer token.
Approvals expire, exchange is one-use, and MFA/password changes invalidate the
approval. Account/payment handoffs also expire and require confirmation; revoked
API tokens cannot be exchanged into a browser session.

The native language preference is sent as `X-Learner-Language`. To update the
Flutter translation asset after editing the website dictionary, run from the
repository root:

```powershell
python backend/scripts/sync-mobile-translations.py
```

## Setup and verification

Run Laravel migrations before using mock exams (the new migration adds `mode`
to practice attempts). Set Flutter's `API_BASE_URL` to the backend's reachable
URL, including `/api/`; use HTTPS for production. The browser must be able to
reach the same backend host. Keep the existing Google, mail, Gemini and payment
provider configuration on the server only. The new screens do not make
unconfigured providers available or expose provider secrets to Flutter.

Run Laravel tests from `backend/`, and `flutter test` plus `dart analyze` from
`mobile/`. Tests cover shared practice and grading, bookmarks, Premium privacy,
MFA, browser exchange, and native draft/submission behavior.

Native device behavior and live Google/mail/Gemini/payment provider flows still
need deployment/device verification. The old Flutter SDK and Android/iOS build
configuration still need modernization before store distribution. Tokens remain
memory-only, so restarting the app requires sign-in; practice drafts and learner
data remain on the server and are available after signing in again.
