# Learn to Drive - Flutter mobile client

This folder contains the imported mobile frontend. Laravel and the web interfaces
are in the sibling `backend/` folder. See [IMPORT.md](IMPORT.md) for source provenance,
changes and outstanding API integration work.

## Development

The imported project requires Dart >=2.18.5 <3.0.0. Dependencies were resolved
using the installed Flutter 3.7.0 / Dart 2.19.0 SDK. Do not assume this legacy
project builds on current Flutter without a separate SDK/dependency migration.

From the repository root, start the local API:

```powershell
cd backend
php artisan serve --host=0.0.0.0 --port=8000
```

In another terminal, from the repository root:

```powershell
cd mobile
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/
```

`10.0.2.2` reaches the host from the Android emulator. For a physical phone,
use the development computer's LAN address; both devices must be able to reach
the server. Include the trailing `/api/`. Use HTTPS for release builds. Do not
put Gemini, merchant secrets, Laravel APP_KEY or database credentials in Flutter
assets or build defines: compiled client configuration is public.

```powershell
flutter analyze --no-pub
flutter test --no-pub
```

Opening the repository in an IDE may default to Laravel; select `mobile/` as
the Flutter project. No nested Git repository or separate push is needed.

## Flutter dependency command compatibility

Flutter 3.7 / Dart 2.19 requires Dart and Flutter VS Code extensions **3.104.0**.
Both extensions are installed at that version and pinned on this machine.
See the [official SDK compatibility table](https://dartcode.org/sdk-version-compatibility/).
Newer extensions reject this SDK and may pass unsupported `--no-example` arguments.
Normal Get Packages and automatic package fetching are enabled again. Alternatively,
use **Tasks: Run Task → Flutter: Install dependencies (compatible)**, or run
`flutter pub get` from `mobile/`. Reload VS Code after changing extension versions.
The archived `backend/storage/flutter-import-source` copy is excluded from Dart
project discovery; `mobile/` is the working application.

## VS Code Gradle runtime

The Gradle extension requires Java 17 or newer. A Java 15 runtime produces
`UnsupportedClassVersionError` (class version 61 versus runtime maximum 59).
This machine now uses a checksum-verified portable Temurin JDK 17 in the ignored
repository `.tools/java17/` directory. Root/mobile VS Code settings and the
multi-root workspace select it through `java.import.gradle.java.home`.
System `JAVA_HOME` is unchanged. After updating this setting, run **Developer:
Reload Window** from VS Code's Command Palette.

The multi-root workspace currently contains this machine's absolute JDK path.
On another computer, set `java.import.gradle.java.home` to that computer's JDK 17
directory. The JDK binaries are not committed. See the
[Adoptium archive installation guide](https://adoptium.net/installation/archives/).
