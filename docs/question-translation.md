> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Question translation

Available on Add Question, Edit Question, and Pending extracted-question review.
Uses the same configured translation provider as PDF translation, including Gemini.

1. Choose Nepali → English or English → Nepali.
2. Click Translate All Details. Non-empty question, four options, and explanation
   are translated separately so options cannot be reordered by the provider.
3. Compare original and translated text in the preview; edit any errors or text
   exceeding the existing field limits.
4. Apply to Form, check the correct answer, then Save or Import as Draft.

Translation alone does not write to the database. Cancel, failure, or a stale
preview leaves the original form intact. Blank explanations stay blank. Duplicate
translated options and overlong text must be corrected before applying.
The correct-answer letter, option order, category, difficulty, image and publication
status are retained. Text inside images is not translated. Existing question answer
verification is invalidated by the model's existing text-change checks when saved.
For extracted questions, the confirmation checkbox is cleared after applying.

Each non-empty field uses a provider request (up to six per question); API quota and
pricing apply. The endpoint requires admin authentication and CSRF protection,
accepts only supported text fields and is limited to 60 requests per ten minutes.
