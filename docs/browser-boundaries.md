# Browser and scraper boundaries

`APP_URL` is the canonical origin for generated links, including password reset,
email verification, mobile handoffs and payment callbacks. Production requests
must use its exact hostname. Local/testing environments additionally accept
loopback hostnames; wildcard subdomains are not trusted. Configure the hosting
server to redirect alternate domains to the canonical domain before Laravel.
Set the reachable HTTPS production origin before deployment.

Responses use `X-Frame-Options: DENY` and CSP `frame-ancestors 'none'`.
Existing content-specific CSP directives are preserved. This intentionally
prevents embedding the application in frames, including same-origin frames.

Learner web and API password login share the per-account (5/minute) and per-IP
(20/minute) limits. Password attempts across the two entry points count together.

Government-notice fetching does not follow redirects and reads at most 2 MB
of HTML. If the official site moves, update the configured allowlisted URL;
do not enable unrestricted redirects. This change does not replace network-level
outbound access controls.
