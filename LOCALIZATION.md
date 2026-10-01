# Localization Rules

- Supported locales are Indonesian (`id`) and English (`en`). Indonesian is the default and fallback locale.
- Put user-facing text in Laravel translation files under `lang/id/` and `lang/en/`; add both translations in the same change.
- Use `__()` or `trans()` in Blade and PHP. Do not add new hard-coded interface labels.
- Keep user-authored content, names, book titles, and identifiers unchanged when switching interface language.
- Format dates with the active application locale. Keep library timestamps in the configured display timezone (`Asia/Jakarta` by default).
- `APP_LOCALE` sets the default locale; a validated user selection in the session takes precedence. Do not edit `.env` directly for a deployment.
- Store only supported locale values in the session. Use Indonesian when the selection is missing or invalid.
