> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Learner study tools

## Question language

Practice setup and the Premium personalized-set form offer English, Nepali and
all languages, independently of the interface language. The form initially
selects the interface language and remembers subsequent choices in the session,
including for the daily challenge. A selection with no matching questions shows an
empty-state error rather than silently serving another language. Smaller banks
produce shorter sets. Existing published question text and answers are not
translated at request time.

The migration indexes existing questions by script: Devanagari in the question
text is classified as Nepali; other text as English. Saving changed question
text updates the classification. This heuristic assumes the existing English /
Nepali bank; mixed-language or other Devanagari-language questions need editorial
review. New reviewed questions receive the same classification automatically.

## Unfinished practice

Radio selections autosave to the account through a private, CSRF-protected
endpoint. A visible status reports success or failure. A manual Save and
continue later button also works without JavaScript and returns to My learning.
The original two-hour expiry still applies. My learning prioritizes the most
recent valid unfinished attempt. Changed, unpublished or expired questions
cannot be resumed or saved; completed attempts cannot be overwritten.

Draft saves never grade answers, reveal solutions, or add practice-history
records. Failed network requests are reported rather than shown as saved. The
browser warns before leaving while a save is pending or unsuccessful.

## Saved resources and recommendations

Signed-in learners can save library resources from their detail pages and open
Saved resources from My learning. Repeated saves are idempotent. Saved resources
remain private to their owner, and archived/deleted resources show an unavailable
placeholder with a removal button, without exposing hidden content.

My learning offers one recommended next step: resume, practise the topic with
the most current mistakes, start a short regular practice, or use flashcards
when there are no published questions. A free account gets topic guidance;
Premium personalized-set restrictions remain enforced by the server.

## Mobile and accessibility

Menus and circular controls have larger touch targets. Filters and cards stack
on small screens, payment/history rows wrap, the chat respects mobile safe-area
insets, and footer content has room below the floating chat control. PDF links
continue to use the browser's document viewer rather than a narrow embed.
Keyboard focus remains visible, Escape closes navigation menus and returns
focus, draft status uses a live region, and reduced-motion preferences disable
decorative motion. Resource and user-entered content retain their original text.

Run `php artisan migrate` when deploying. No external AI call is needed for
these tools. Automated tests cover language selection, draft ownership and
expiry, grading integrity, bookmarks, visibility and recommendation behavior.
Device/browser and screen-reader checks are still recommended before release.
