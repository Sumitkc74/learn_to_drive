> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Local backup and recovery

The current deployment uses SQLite. Backups supports that engine and local public,
protected-media, and learning-content storage. For other databases or remote disks,
use native backup tooling before switching this feature to those environments.

Use Backups → Create and verify backup, or:

    php artisan backup:local
    php artisan backup:local --verify=BACKUP-UUID

PHP ZIP must be enabled. Run during a quiet period: SQLite is snapshotted using
VACUUM INTO, and detected database changes during collection fail the backup rather
than label it verified. Stop workers and web writes for a deliberately quiescent backup.

Private archives are stored in storage/app/private/backups. Each archive contains
database.sqlite, managed uploads under files/{disk}/, and a SHA-256 manifest.
Generated PDF previews and translation build folders are excluded. Application code,
dependencies, .env secrets and external services are not included. Keep the matching
code revision and environment configuration separately and securely.

Verification checks the archive hash, every bundled file hash, SQLite integrity in
a separate restored database copy, and the presence of referenced original media
and review files. It does not overwrite the running database or application files.
Keep an off-device protected copy: a local backup alone cannot survive disk loss.
Archives contain personal data and password hashes; never place them in public storage.

## Restore procedure

1. Choose a verified backup and record the matching application revision. Stop the
   web application, scheduler and queue workers. Preserve the current database and
   storage as a separate recovery copy; do not overwrite the only remaining copy.
2. Verify the archive with the command above. Extract the trusted archive into a
   new private staging directory, not directly over the running application.
3. In an isolated copy of the application, configure DB_DATABASE to the extracted
   database.sqlite. Restore files/public, files/protected-media and files/learning-content
   into the corresponding disk roots from config/filesystems.php. Retain private visibility.
4. Run SQLite integrity checks, `php artisan media:check-storage`, and smoke tests:
   admin login, public PDF/image access, private draft access denial, content lists,
   and pending review records. Do not run live scraping or AI jobs in the staging copy.
5. After reviewing the restored application, switch the stopped production application
   to the verified database and storage directories. Clear Laravel configuration/view
   caches, recreate the public storage link if needed, then start web, workers and scheduler.
6. Check logs and critical workflows again. Keep the pre-restore recovery copy until
   the restored deployment has been accepted.

The automated restore check tests the database and bundled bytes; it does not replace
the isolated application smoke test or a deliberate production restore decision.
