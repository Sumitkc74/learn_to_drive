# Mobile development setup

Flutter is configured locally to use `D:/flutter`, the Android SDK in
`.tools/android-sdk`, and Java 17 in `.tools/java17/jdk-17.0.20.1+1`.
These downloaded tools are ignored by Git. Restart VS Code after changing SDKs.

Verified on 2026-09-26: Android toolchain and licenses pass `flutter doctor`;
debug APK builds at `mobile/build/app/outputs/flutter-apk/app-debug.apk`.
Flutter analysis is clean, all 14 Flutter tests pass, and 26 focused Laravel
authentication, browser boundary, CORS and mobile parity tests pass.
The Android migration also fixes the debug manifest override, updates the
lifecycle plugin, disables legacy Jetifier, and gives Gradle 4 GB with two workers.

From `mobile/`:

```powershell
flutter doctor -v
flutter analyze
flutter test
flutter build apk --debug
flutter devices
flutter run -d <device-id>
```

Connect an Android phone with USB debugging enabled to run on a device.
The SDK installation does not include an emulator or system image.

On this Windows machine Java downloads require the Windows certificate store.
If a Gradle or SDK download reports certificate validation errors, set this for
the current PowerShell session before building (do not disable TLS validation):

```powershell
$env:JAVA_TOOL_OPTIONS = '-Djavax.net.ssl.trustStoreType=Windows-ROOT -Djavax.net.ssl.trustStore=NONE'
```

Native sessions use encrypted platform storage; expired sessions are discarded.
If the platform vault is unavailable, login still works for the current process
but persistence is unavailable. Logout removes the saved session. Server-side
revocation and authorization remain authoritative.

`learntodrive://return` resumes the mobile app from a connected browser session.
The link carries no credentials and grants no access. It is a custom scheme,
not a verified HTTPS app link. Device testing is still required for browser
sign-in, payment returns and email verification.

Production: configure exact `CORS_ALLOWED_ORIGINS` (comma-separated), HTTPS
`APP_URL`, mail/OAuth/payment provider settings and release signing privately.
An empty CORS setting defaults to `APP_URL`; native clients are unaffected by
browser CORS. Rotate previously exposed provider keys in their provider consoles.
iOS builds and Keychain behavior must be verified on macOS with Xcode and a
provisioned signing identity before release.
