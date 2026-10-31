# Admin AI assistant

The floating admin assistant uses fixed, read-only queries. It sends the question,
recent conversation and selected database context to Gemini. It cannot execute
model-generated SQL, modify records, send replies or fetch arbitrary URLs.

Examples:

- “How many users were added today?” — created_at within the Nepal calendar day;
  yesterday is also provided. Counts include local demo data.
- “How many Premium users?” — active non-admin users with PremiumUser role or an
  unexpired premium_until, matching application access rules. This is not a count
  of paying customers. Payment counts distinguish live and test transactions.
- “Any problems reading content?” — draft-question readiness, extraction worker
  status, failed/stalled extraction runs, source runs and the last saved storage
  audit. Audit timestamp and limited record examples are included; no scan is
  triggered. No audit means unknown, not healthy.
- “Are notices updated?” — visible/expired/status counts, recent updates, latest
  government import fetch and review states. “Notice #12” adds that notice's text.
- “Is https://… working?” — exact URL matches to saved notices/resources and their
  recorded revision checks. No live HTTP probe is performed. Use Source check
  history and the relevant content review check to refresh evidence.
- “How do I reply to today's complaint?” — selects a sole complaint or asks which
  ticket when several match. Opening a ticket supplies page context. The latest
  ten replies are read again for follow-ups; nothing is sent to the learner.

Aggregate context also includes school and booking statuses, support queues and
learning-library totals. Other periods and records are not silently guessed.
Database timestamps do not prove factual correctness; neither approval nor AI
screening certifies current official notices. The assistant must explain missing
or stale evidence and link back to admin tools. Generated wording still requires
human review. Account credentials, MFA secrets, payment tokens, file paths and
user contact lists are excluded from these summaries. Free-text support messages
and selected notice descriptions are treated as untrusted reference data.
