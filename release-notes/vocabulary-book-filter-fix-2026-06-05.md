# Vocabulary Book Filter Hardening - 2026-06-05

## Root Cause

Vocabulary filtering after the unique-word-id migration trusted `chapters.unique_word_ids` whenever any ids were present for the selected book. Old or migrated chapters can still have useful `unique_words` while `unique_word_ids` is null, empty, stale, cross-record, or mismatched. When stale ids pointed at another word, the filter could return the wrong vocabulary row. Chapter-only filtering also did not apply the collected chapter vocabulary constraints because the backend only checked the book filter flag.

## Tests Added

- Kept the existing duplicate word text coverage proving valid `unique_word_ids` are used instead of text fallback.
- Added coverage for old/migrated chapters where `unique_words` exists but `unique_word_ids` is null or empty.
- Added coverage for stale/mismatched `unique_word_ids` so the filter falls back to the chapter `unique_words` instead of returning a wrong id result.
- Added coverage that chapter filtering uses the same fallback behavior as book filtering.

## Backend Logic Changed

- `VocabularyService::buildSearchRequest()` now treats either a selected book or selected chapter as a chapter vocabulary filter.
- Collected word ids are validated against the current user, language, and available chapter `unique_words` before id-based filtering is used.
- Valid id sets remain the preferred path, preserving duplicate-word behavior for correctly migrated chapters.
- If ids are missing, empty, or clearly unusable, filtering falls back to `unique_words` text for the selected chapter/book scope.
- Phrase filtering now also respects chapter-only filtering via the same selected chapter/book condition.

## Remaining DB / Backfill Risks

- Runtime fallback cannot perfectly disambiguate duplicate word text when migrated chapter ids are stale and only `unique_words` text remains reliable.
- Existing production data may still contain null, empty, stale, or mismatched `unique_word_ids` / `unique_phrase_ids`.
- Large libraries should not be repaired inside request handling.

## Repair Command Follow-up

A separate Artisan repair command is still useful, but was not implemented in this pass because the runtime fallback is sufficient for the tested failure modes.

Suggested command design:

- Scan processed chapters in chunks.
- For each chapter, decode `unique_words` and rebuild `unique_word_ids` from `encountered_words` constrained by chapter `user_id` and `language`.
- Rebuild `unique_phrase_ids` from processed text phrase ids or by re-running the existing chapter helper.
- Report chapters with ambiguous duplicate word text instead of guessing.
- Support dry-run mode, per-user filtering, and per-book filtering before allowing writes.
