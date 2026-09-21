> **Folder layout:** Run Laravel commands from `backend/`; application-relative paths below refer to that folder.

# Learner interface languages

The learner website has an English / नेपाली menu in its navigation bar. Open the globe icon and choose a
language. The Learn to Drive app name stays unchanged in both languages. The preference is stored in the session and an
encrypted, HTTP-only cookie lasting one year, so guests can use it too. It is a
browser preference; it does not yet sync between devices. English is the default.

Navigation, authentication forms, settings, practice controls, support, premium
pages, chat controls and validation messages use Laravel translation files in
`lang/ne.json` and `lang/ne/`. The admin interface remains English. No external
translation service is called to render the interface.

Existing Nepali traffic-sign names and notice titles/descriptions are preferred
in Nepali mode, with the original content as fallback. PDFs, question text,
answers and user messages retain their stored language. The library's separate
content-language filter still supports English, Nepali and all languages; changing
the interface language does not hide resources or alter practice scoring.

Keep new interface text in translation calls and add corresponding Nepali keys.
Translate labels, never database enum values, form values, routes or user content.
Use placeholders for sentences containing numbers or other dynamic values.
Nepali copy should receive a native-speaker editorial review before release.
