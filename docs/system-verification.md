# Local verification — 9 October 2026

- Laravel: 311 tests / 2,288 assertions passed using the isolated test database.
- Flutter: all 20 tests passed, including narrow/landscape/tablet layout checks.
- All local database migrations applied; routes register successfully.
- Duplicate audit: no candidate duplicates in the 15 audited tables, and no
  inconsistent lesson booking claims. This is not an all-database uniqueness proof.
- Media audit: no missing referenced files; five possibly unused uploads remain
  for review under Media Library → Storage Check. No uploads were deleted.
- Local school/vehicle/lesson tables are empty. School maps and lesson listings
  require approved schools with coordinates and published instructor slots.

## Build findings

System Node 20.10 cannot run the installed Vite. Package metadata now declares
the required Node version. Laravel assets compile with Node 22:

```powershell
cd backend
npx --yes --package=node@22 node node_modules/vite/bin/vite.js build
```

Install a compatible Node version for ordinary `npm run build`. Bootstrap Sass
deprecation warnings remain but do not prevent compilation.

Flutter web compiles as JavaScript. Its legacy bootstrap was replaced with the
installed Flutter SDK's bootstrap pattern; web app branding and orientation were
updated. GetStorage currently prevents WebAssembly compilation, which is not
required for the JavaScript web build.

## Not verified by these checks

Release mobile/web builds require `--dart-define=API_BASE_URL=https://YOUR-HOST/api/`.
The default HTTP Android emulator address is for local debug only; release API
requests require HTTPS. A successful build does not prove deployed connectivity.

Live Gemini, mail, Google sign-in, payment providers, external map tiles, hosted
scheduler execution, physical devices, full visual browser coverage and restore
testing were not exercised. No live payments, emails or production changes were
made. Nothing was pushed.
