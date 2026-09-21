> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Repository and sensitive-data audit

Audit date: 2026-09-17.

## Scope and result

Scanned 49 commits and 2,761 blob versions reachable from local branches,
remote-tracking branches and tags. A read-only GitHub reference check confirmed
the current remote branch tips were included in this history.

No confirmed private keys or API credentials were found by the pattern checks.
Exact-match checks for the two configured local credential values found no
matches in that history or current files eligible for commit. Empty database
password examples were false positives. The local `.env` was not tracked.

No commits were removed or rewritten, and nothing was pushed. These checks do
not prove every possible secret or personal detail is absent. Git's internal
Codex checkpoint references include a broken object, so the scan explicitly
used branches, remote-tracking branches and tags; it did not cover dangling
objects, all reflogs, GitHub pull-request refs, forks or other clones.

## Changes

- Ignore environment variants, database dumps, SQLite databases, private key
  files and generated storage output. Ignore rules do not untrack existing files.
- Stop the administrator seeder from resetting an existing account. Initial
  administrator creation outside tests requires `SEED_ADMIN_PASSWORD` in the
  private environment configuration. Remove that setting after initial setup.
- PDF helper failures log generic diagnostics rather than captured process
  output that could contain document text.
- Custom website scraping rejects URL query parameters that name credentials,
  including nested parameters. Public HTTPS, private-network blocking, DNS
  pinning and disabled redirects remain in place. This is not a general-purpose
  detector of personal information embedded in public pages or URL paths.

## External processing and operational follow-up

The website importer reads public pages and discovers supported document/image
links. It does not need browser login cookies. Administrators must still choose
appropriate public sources and review discovered material before publication.

Gemini receives content for enabled AI screening/translation and premium chat;
chat includes the submitted question, limited conversation history and learning
context. Do not submit credentials or confidential documents to these features.
Private review storage restricts web access, but does not mean content is never
sent to the configured AI provider. Backups contain private application data
and must remain protected outside Git.

Rotate the Gemini API credential previously shared in conversation, even though
it was not found in Git. Update the private environment and clear Laravel's
configuration cache afterward. Do not casually regenerate `APP_KEY`: existing
encrypted data needs a planned migration. Git cleanup cannot revoke credentials
or erase copies outside this repository.
