# Account security fixes

Browser sessions record the account password hash at sign-in and check it on
subsequent requests. A password change invalidates other browser sessions on
their next request. Mobile password changes also rotate the remember-me token
and revoke other API tokens while preserving the current mobile token.

Browser sessions without a password marker are rejected on their next request.
Users with pre-update sessions must sign in again; no manual session-file
deletion is required.

POST /api/userHistory is retired (410). GET remains available for private history.
New history must come from submitting an owned server-created practice attempt.
Password recovery reports mail failures internally but returns the same public
response as an unknown address. This removes the error-response distinction;
it is not a claim that synchronous email delivery has constant response timing.

The legacy public questions endpoint now returns question text and options only,
without solutions or explanations. Old mobile mock-exam navigation delegates to
the server-graded practice flow. Answer disclosure after completed practice and
in Premium revision remains intentional.

Both clients use one database transaction and user-row lock for MFA enrollment
and removal. Enrollment checks the current persisted state under that lock;
recovery-code consumption and removal are atomic.

Previously exposed provider credentials still need rotation in their provider
console. Updating code does not revoke an exposed key. Laravel APP_KEY rotation
requires a separate encrypted-data migration plan; do not replace it blindly.
