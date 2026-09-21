> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Learner website

Open `/` on your Laravel server (normally http://127.0.0.1:8000). Admin sign-in remains `/login`; learner sign-in is `/learn/login`. Existing accounts can sign in, and new learner registration always creates a User account.

Visitors can browse traffic signs, question banks, exam information, tutorials, vision exercises, and current notices. PDF collections retain one file per item and can be filtered by English or Nepali. Content comes from the same records the admin manages. Scraped drafts remain in review until approved; draft, scheduled, expired, and archived notices are excluded.

Signed-in learners can start randomized practice sessions of up to 5, 10, or 20 published questions. Answers are graded on the server. Results and available explanations appear after submission. Completed sessions appear in My learning and feed existing admin practice-history analytics. Sessions expire after two hours. If a question is changed or unpublished, its old session is blocked; the saved score remains in history.

Each resource and practice question has a Report link. Reports and general feedback go to the existing admin Support area. Learners can read replies and continue their own conversations at `/learn/support`. These pages require an active account and never expose another learner's tickets or results.

## Daily learning and flashcards

My learning includes a daily goal: complete one practice session. Activity is counted by Nepal calendar days (Asia/Kathmandu); multiple sessions on the same day count as one streak day. A streak remains active until the learner misses a full day. Accuracy is calculated across answered questions from completed web sessions. Milestones unlock after 1, 5, and 20 completed sessions. These are learning milestones, not certifications.

Traffic-sign flashcards at `/learn/sign-flashcards` are public. They use existing traffic signs with attached media, show one card at a time, and let the learner reveal its name and description. Flashcards do not affect scored practice statistics. If no published questions are available, the daily challenge points learners toward flashcards instead.

## Running locally

```powershell
php artisan migrate
php artisan serve
```

The learner site uses `public/css/learner.css` and Blade templates; no frontend build is required. If your environment uses the previously configured PHP extensions, set `PHP_INI_SCAN_DIR` in the terminal as documented for the existing project. Do not put PowerShell commands in `.env`.

Run focused verification with `php artisan test --filter=LearnerWebTest`. The learner app is a future integration; this implementation is the web interface. Vision exercises are educational, and practice scores are not official exam results. Password recovery uses the existing email/reset flow and requires configured mail delivery.

## Navigation

Home introduces the three-step learning journey. Library (/learn/library) holds the resource collections. Practice handles session setup and answers. Notices lists current announcements. My learning owns the daily goal, streak, milestones and history. Help owns support conversations. Shared navigation stays consistent, with a current-section indicator; promotional cards and progress panels are not repeated across pages.
