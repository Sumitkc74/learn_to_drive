# Flutter source import

- Source: https://github.com/Sumitkc74/learn_to_drive_app_fyp
- Source commit: `cf275eb5107a05f7ec3bc7fe10f23bfdfe30fa6a`
- Imported: 2026-09-18
- Source directory: `learn_to_drive/`
- Destination: `mobile/`

This is a source snapshot managed by the Laravel repository, not a submodule.
The upstream Git history and root IDE configuration were not imported. Existing
platform projects, assets, dependency lockfile and application screens are kept.
The Dart package name remains `first_app` to preserve package imports.

Import adjustments:

- Corrected case-sensitive Dart package imports to match filesystem names.
- API base URL uses `--dart-define=API_BASE_URL=...` instead of a private LAN IP.
- Shared HTTP headers include the current bearer token for protected endpoints.
- Registration sends password confirmation; password changes use the current
  authenticated PUT endpoint and field names.
- Removed the embedded Khalti test public key and disabled the obsolete direct
  payment flow. Use verified website checkout until mobile payments are integrated.
- Android display name is Learn to Drive. Internet permission is available in
  release; cleartext HTTP is permitted only in debug builds.
- Added signing/configuration exclusions and replaced the unrelated generated
  counter test with a test of the app's actual action button.

## Learner feature integration

The imported navigation now uses native learner screens backed by the same
Laravel services as the website. Library, bookmarks, resumable practice, mock
exams, progress, Premium chat/revision, support, profile edits, MFA and payment
history are available through the app. Google sign-in/linking and payment checkout
use explicit browser handoffs; provider secrets remain on the server.

See [learner feature parity](../docs/learner-feature-parity.md) for the feature
matrix, migration, language sync and verification details. Tokens are memory-only.
The original Flutter/platform dependencies still require modernization before
store distribution. Device and live provider testing are not implied by automated
unit/feature tests.
