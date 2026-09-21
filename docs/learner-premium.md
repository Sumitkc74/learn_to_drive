> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Learner Premium

The Premium page is `/learn/premium`. Existing `PremiumUser` accounts and admins can use its chatbot and personalized revision modules. Ordinary users retain the free library and regular practice. Premium routes and revision-mode submissions enforce access on the server.

## Upgrade workflow

When online checkout is not configured, Request Premium upgrade creates a Support conversation. An open request is reused. No payment is taken and a request does not grant access.

Admins review the request under Support, then edit the learner under Users and change their role to **Premium User**. Reply to the support conversation and resolve it once complete. To remove access, change the role back to User. Admin-granted PremiumUser roles have no automatic expiration. Gateway purchases use expiring paid access; see premium-payments.md. There is no recurring billing.

## Revision modules

Modules use the learner's latest 100 completed web practice sessions, taking the most recent answer for each question. Only currently published questions with unchanged content are eligible. Incorrect answers are grouped by category, show the correct option and the existing explanation if available, and can be retried in sets of up to 20. A correct retry removes that question from revision. Missing explanations are labelled rather than invented.

## Study chatbot

The chatbot uses the existing GEMINI_API_KEY and an optional GEMINI_CHAT_MODEL (defaults to the configured translation model). Uses Google's [generateContent API](https://ai.google.dev/api/generate-content). Messages, four previous successful conversation turns, and up to five current questions the learner missed are sent to Gemini. Account details are not added to the prompt. The interface explains this before sending.

Transcripts are stored in premium_chat_turns and visible only to the owner. The page displays the latest 20 turns. Replies are escaped as plain text, not executable HTML. AI may be incorrect and is not a substitute for current official guidance.

PREMIUM_CHAT_DAILY_LIMIT defaults to 30 attempts per user per Nepal day. Failed calls also count to control repeated provider usage; a five-per-minute limit also applies. Gemini API usage may incur provider charges even though this upgrade flow does not collect payment. No live paid call is performed by automated tests.

Run `php artisan migrate` after deployment and `php artisan test --filter=LearnerPremiumTest` to check access, revision behavior, conversation privacy and quota handling.

## Personalized practice and focused explanations

The revision page now offers 5, 10, or 20-question personalized sets. It starts with up to half missed questions, adds other published questions from the same categories, and fills remaining slots with further mistakes when needed. It never invents scored questions. Small question banks produce shorter sets. The selection uses completed web practice history, not imported legacy app test history.

Explain with Gemini opens the chatbot with a selected missed question. The server verifies that the selected question belongs to the learner's current revision list before sending it. The existing GEMINI_API_KEY is reused; no separate key is needed. Missing stored explanations can be discussed with AI, and responses remain labelled as AI rather than official solutions.

## Floating study assistant

Premium learners get a floating chat panel on learner pages, except the full chat page and MFA challenge. The widget uses the same authenticated endpoint, quotas and stored transcript as full chat. Requests include platform facts and explicit topic restrictions: application help, driving study, road safety and exam preparation. Off-topic requests should receive a brief redirection. This restriction is model-instruction based, not a guaranteed content classifier. Replies render as plain text. The widget supports Escape to close, keyboard focus, sending status and recoverable errors.
