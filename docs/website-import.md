> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Import from Website

Open Learning Content → Import from Website. Enter a public HTTPS page and choose
Question Banks, Traffic Signs, or Vision Tests. Scan, select relevant results, then
Add Selected for Review. Use Save Private Copy on each review item to download it.

The scan reads one HTML page (up to 2 MB), lists up to 50 unique files, and expires
after 30 minutes. Only the latest scan is retained in your session. PDF discovery
uses direct .pdf links; image discovery uses img src/data-src. It does not execute
JavaScript, crawl other pages, follow redirects, or classify image contents.
Logos and unrelated images can appear; select only relevant resources. Download
MIME/size checks still apply. No files are published by scanning or selecting.

Custom sources are labelled Website, not official. Titles come from link text or
image alt text, with filename fallback. Existing URLs retain their current review
status. Source and file URLs remain attached to the review item.

Requests require public HTTPS DNS hostnames and port 443. Private/reserved IPv4
destinations, raw IP URLs, credentials and local hostnames are blocked. Each scan
or download resolves and pins a checked public IPv4 address using cURL; proxies
and redirects are disabled, and TLS verification stays enabled. IPv6-only sites
are not supported. Downloads repeat validation to prevent DNS rebinding.
