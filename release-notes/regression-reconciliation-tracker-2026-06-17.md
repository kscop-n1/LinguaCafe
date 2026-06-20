# Regression Reconciliation Tracker - 2026-06-17

Objective: compare the old LinguaCafe implementation with the current migrated codebase before applying additional fixes. The old repository is a behavioral reference, not a source to copy blindly.

Old reference root used for this tracker: `/data/git/old_LinguaCafe/LinguaCafe`
Current root: `/data/git/LinguaCafe`

## Workflow Rules

- Tracker was created before new reconciliation fixes.
- Every future fix must reference an entry below.
- Prefer shared components/styles where old behavior was shared.
- Do not reintroduce obsolete Vue 2 / Vuetify 2 APIs into the migrated code.
- If old behavior is also wrong, classify it as a normal bug rather than a migration regression.

## File Map

| Area | Old implementation | Current implementation | Notes |
| --- | --- | --- | --- |
| Vocabulary tokenizer/parser | `app/Services/TextBlockService.php`; `app/Services/VocabularyService.php`; `app/Models/EncounteredWord.php` | Same files plus `app/Console/Commands/CleanupNonWordVocabulary.php` | Current adds central `TextBlockService::isVocabularyToken`, query scope, cleanup command, and hyphen coalescing. |
| Vocabulary filtering | `app/Services/VocabularyService.php`; `app/Http/Controllers/VocabularyController.php`; `resources/js/components/Vocabulary/Vocabulary.vue` | Same files | Current optimizes book/chapter lookup and can use `unique_word_ids` with text fallback. |
| Chapter pagination/statistics | `app/Services/ChapterService.php`; `app/Http/Controllers/ChapterController.php`; `resources/js/components/Library/BookChapters.vue` | Same files plus `tests/Feature/ChapterTest.php` | Current moved to server pagination and explicit pagination metadata. |
| Table/list action buttons | Admin table components; `resources/js/components/Vocabulary/Vocabulary.vue`; `resources/js/components/Library/BookChapters.vue`; Vuetify 2 icon buttons | Same components plus shared `.table-action-button` in `resources/sass/app.scss` | Current should keep compact shared dimensions instead of page patches. |
| Reader/Review toolbars | `resources/js/components/TextReader/TextReader.vue`; `resources/js/components/Review/Review.vue`; `resources/sass/TextReader/TextReader.scss` | Same files plus shared `.vertical-toolbar-button` in `resources/sass/app.scss` | Current ports Vuetify 2 circular icon-button behavior explicitly. |
| Modals/dialogs | Admin edit dialogs, Vocabulary edit/import, Reader settings; old `v-model`, `height`, `scrollable`, `dark`, `small`, `right` props | Same components with `model-value`, `update:model-value`, `size`, and `.app-dialog-card` | Old dialog sizing was not always ideal; do not blindly restore fixed heights. |
| Dark theme | `resources/sass/DarkMode.scss`; `resources/js/services/ThemeService.js`; CSS using `var(--v-*-base)` | `resources/js/themes.js`; `resources/sass/DarkMode.scss`; CSS using `rgb(var(--v-theme-*));` | Current correctly needs Vuetify 3 variables, but broad contrast needs visual audit. |
| Sidebar/mobile bottom controls | `resources/js/components/Layout.vue`; inline icon/span/flag layout in bottom `v-list` | `resources/js/components/Layout.vue`; shared bottom-list classes in `resources/sass/app.scss` | Current uses Vuetify 3 prepend slots to recreate old list item alignment. |
| Manual/documentation links | `resources/js/components/Vocabulary/VocabularyImportDialog.vue`; `/user-manual/vocabulary-import`; `resources/js/components/UserManual/Pages/UserManualVocabularyImport.vue`; `manual/Setup.md` | `resources/js/components/Vocabulary/VocabularyImportDialog.vue`; `/user-manual/Setup#Importing%20Vocabulary%20into%20LinguaCafe`; `manual/Setup.md`; router route `/user-manual/{currentPage?}` | Current manual structure differs; deep link must target existing markdown route/anchor. |

## REG-001: Vocabulary tokenizer accepts non-word tokens

Status: Verified
Area: Vocabulary
Observed current behavior: Reported invalid tokens such as `#`, `'s`, `):`, `+1`, `+10d6`, `+2/+4`, `1d10+db`, `1d4+poison`, `1d6/`, `-fis`, `-m`, `/12*mp` appeared in Vocabulary.
Old behavior: Old `TextBlockService::createNewEncounteredWords` inserted every processed word except existing words and `NEWLINE`; old `collectUniqueWords` also collected every lowercased processed token. CSV import only rejected spaces, length, empty word, and invalid stage. See old `app/Services/TextBlockService.php:286-330`, `:355-360`, and old `app/Services/VocabularyService.php:431-520`.
Current implementation: Current `TextBlockService::classifyVocabularyToken` returns structured validity/reason/ambiguity data; `isVocabularyToken` is its boolean wrapper. The lexical grammar requires each segment to start with a Unicode letter, permits following combining marks, and permits internal apostrophe/hyphen separators. Creation, unique collection, CSV import, search queries, review queries, statistics, and chapter/book counts use this classifier or the matching SQL valid-token scope in `EncounteredWord`. See current `app/Services/TextBlockService.php`, `app/Models/EncounteredWord.php`, `app/Services/VocabularyService.php`, and `app/Console/Commands/CleanupNonWordVocabulary.php`.
Difference classification: Old code also had the issue
Evidence: Tests cover reported invalid tokens in `tests/Feature/TextBlockServiceTest.php:15-19`, search filtering in `tests/Feature/VocabularyTest.php:323-343`, CSV import rejection in `tests/Feature/VocabularyTest.php:346-377`, and cleanup repair in `tests/Feature/CleanupNonWordVocabularyTest.php:18-142`.
Recommended action: Keep current approach but fix regression
Risk: High
Tests needed: Unit, Backend feature
Fix status: Applied and verified on 2026-06-18.
Notes: This was not purely a migration regression. Old behavior was permissive. The remaining risk is over-filtering language-specific tokens; keep future examples in `TextBlockServiceTest`.

Follow-up analysis on 2026-06-18:

- The current boolean classifier covers the reported examples but cannot explain why a token was rejected. Cleanup therefore cannot group candidates by reason or distinguish safe automatic cleanup from unknown suspicious data.
- Pipeline enforcement is broad: `TextBlockService` word counting/collection/insertion, phrase creation, CSV import, Vocabulary search, Review search, Book/Chapter counts, and Statistics use the classifier or SQL valid-token scope.
- Pipeline gap found during analysis: `Goal::getTodaysReviewGoalQuantity()` counted legacy invalid review rows because it did not apply `validVocabularyToken()`; this was closed by the follow-up implementation below.
- The cleanup command is dry-run by default and apply mode currently quarantines every invalid row by setting stage `1`, clearing `next_review`, repairing chapter `unique_words`/`unique_word_ids`, and recalculating book totals. It does not report rejection reasons, related chapter/book counts, review/highlight state, deletion safety, ambiguity, or idempotent backfill decisions.
- Flashcard tables were removed by migration `2024_05_29_232455_delete_flashcard_tables.php`; review/highlight state is stored on `encountered_words` itself through stage/SRS fields rather than separate related records.
- Word metadata backfill exists only in old migration `2022_09_22_110938_modify_lessons_table_4.php`. It matched by language without user scope and did not detect duplicate word text, so it is not a safe durable repair mechanism.
- Phrase metadata backfill exists in migration `2026_05_22_000002_add_unique_phrase_ids_to_chapters.php`, and current chapter processing calls `refreshUniquePhraseIds()`. Null legacy rows can still remain and currently require the REG-003 processed-text fallback.

Planned failing tests before production changes:

- Structured classifier returns stable rejection reasons for every reported invalid category while preserving Unicode, diacritics, contractions, and lexical hyphens.
- Cleanup dry-run groups candidate rows by reason and reports scope, related IDs/counts, review/highlight state, safety, and ambiguity without mutation.
- Apply deletes only safe unambiguous unused rows, quarantines rows with user/review history, leaves unknown suspicious rows for manual review, repairs chapter metadata, and is idempotent.
- Review-goal counts exclude invalid legacy rows.
- Metadata backfill fills missing unambiguous word/phrase IDs, preserves valid current IDs, reports duplicate-text ambiguity without mutation, and supports mixed legacy/current chapters.

Follow-up result on 2026-06-18:

- Classifier design: `TextBlockService::classifyVocabularyToken()` now returns `valid`, `reason`, and `ambiguous`; `isVocabularyToken()` remains the shared boolean wrapper. Stable reasons include punctuation-only, signed/number-only, dice notation, dice/stat arithmetic, arithmetic, leading/trailing fragments, standalone apostrophe suffixes, slash/star expressions, number-letter mixtures, configured skips, valid lexical tokens, and unknown suspicious tokens.
- Valid lexical behavior remains Unicode-letter based with combining marks and internal apostrophes/hyphens. Tests preserve Cyrillic, diacritics, CJK words, contractions, `stir-crazy`, `well-known`, `mother-in-law`, and `state-of-the-art`.
- Persistence hardening: manual word spelling updates now use the shared classifier and return a controlled `422` for invalid single-token spelling. Multi-word content remains supported by phrase flows, not by single-word spelling.
- Pipeline closure: `Goal::getTodaysReviewGoalQuantity()` now applies `validVocabularyToken()`, matching Vocabulary, Review, Statistics, Book, and Chapter count queries.
- Review finding: the original SQL valid-token scope used a separate ASCII/POSIX approximation that rejected decomposed Unicode diacritics accepted by the PHP classifier. It now uses the same ICU letter/combining-mark plus internal apostrophe/hyphen grammar, with a database-backed parity test for composed/decomposed diacritics, Cyrillic, CJK, contractions, hyphenated words, and reported invalid tokens.
- Cleanup policy: dry-run groups all candidates by reason and user/language/token scope, reports encountered-word IDs/counts, review/highlight state, removed flashcard count (`0`, because those tables no longer exist), example-sentence references, chapter/book associations, deletion safety, and manual-review status.
- Apply policy: pristine, unambiguous invalid rows are deleted after chapter metadata repair; rows carrying stage/SRS/user-entered lexical metadata or example-sentence references are quarantined at stage `1` with review scheduling cleared; unknown suspicious tokens are never mutated automatically. Chapter `unique_words`, `unique_word_ids`, chapter word totals, and affected book totals are repaired transactionally. Repeated apply runs make no further automatic changes.
- Destructive-operation safety: both maintenance commands default to dry-run. Cleanup apply is permitted only after candidate review and never deletes rows with user/SRS/example-sentence history; backfill apply writes only unambiguous scoped metadata. Unknown tokens, unresolved words, and duplicate-text candidates are reported but not guessed or mutated.
- Metadata backfill: new `linguacafe:backfill-vocabulary-metadata` command is dry-run by default, scopes reconstruction by user and language, preserves valid existing IDs, reconstructs phrase IDs from the chapter's own processed text, fills word IDs only when each uncovered text has exactly one candidate, and reports unresolved or duplicate candidates instead of guessing. Apply mode is idempotent.
- Residual risk: duplicate `encountered_words.word` rows remain intentionally ambiguous unless an existing chapter ID already disambiguates them. Those chapters continue using the REG-003 text fallback for words. The fallback remains temporary for unambiguous legacy data but permanent removal requires a product/data decision for duplicate rows.

Changed files:

- `app/Services/TextBlockService.php`
- `app/Services/VocabularyService.php`
- `app/Http/Controllers/VocabularyController.php`
- `app/Models/Goal.php`
- `app/Models/EncounteredWord.php`
- `app/Console/Commands/CleanupNonWordVocabulary.php`
- `app/Console/Commands/BackfillVocabularyMetadata.php`
- `tests/Feature/TextBlockServiceTest.php`
- `tests/Feature/VocabularyTest.php`
- `tests/Feature/CleanupNonWordVocabularyTest.php`
- `tests/Feature/BackfillVocabularyMetadataTest.php`
- `tests/Feature/MigrationSmokeTest.php`

Isolated dry-run/apply evidence:

- Cleanup dry-run scanned 6 rows and classified 3 invalid candidates: one dice/stat row safe to delete, one reviewed dice row requiring quarantine, and one unknown suspicious row requiring manual review.
- Cleanup apply deleted 1 row, quarantined 1 row while preserving its user note, skipped 1 ambiguous row, repaired 1 chapter, and recalculated 1 book. Chapter/book word totals changed from 4 to 2. A second dry-run proposed no further delete/quarantine actions.
- Metadata dry-run detected duplicate text `shared` with 2 candidate word IDs and refused to guess. It identified 1 safe phrase-ID repair. Apply changed the phrase metadata only; the second dry-run reported `chapters_would_change: 0` while retaining the explicit duplicate ambiguity.
- No cleanup or backfill apply was run against the production database.
- Production-like dry-run review on 2026-06-20 is documented in `release-notes/production-maintenance-dry-run-report-2026-06-20.md`.
  - The June 19 production backup was restored into an isolated disposable database; live production data was not queried or mutated.
  - Cleanup found 2,398 invalid records: 449 mechanically safe-delete candidates, 1,509 already ignored records, 440 manual-review candidates, and no pending quarantines.
  - Blanket cleanup apply is not recommended because mechanically pristine candidates include debatable lexical forms such as `1930s`, `26-year`, ordinals, abbreviations, and URLs.
  - Backfill would change all 1,972 processed chapters by removing 18,980 word IDs and 16 phrase IDs, with no additions.
  - The 16 phrase removals are wrong-user references with same-text user-scoped replacements available; current backfill removes but does not remap them.
  - No duplicate word text exists within user/language scope. The 440 cleanup ambiguities are suspicious-token classifications, not duplicate database rows.
  - Pre/post row counts and checksums were identical for all affected tables. No apply mode was executed.

Verification:

- Full PHPUnit suite after review: 68 tests, 844 assertions passed.
- Focused REG-001/REG-003/REG-004 suite after review: 54 tests, 799 assertions passed.
- `npm run check:migration` passed, including the production Vite build.
- `node --check scripts/check-legacy.js` passed.
- `npm run check:css` passed with 0 errors and 359 existing warnings.
- PHP syntax checks passed for all changed production PHP files.

## REG-002: Valid hyphenated words and phrases

Status: Verified
Area: Vocabulary
Observed current behavior: Valid examples such as `stir-crazy` and phrase `go stir-crazy` were suspicious after stricter token filtering.
Old behavior: Old code did not have central token validation or explicit hyphen-token coalescing in the PHP processing path; it relied on tokenizer output. See old `app/Services/TextBlockService.php:142-170` and `:355-360`.
Current implementation: Current code validates internal hyphens and coalesces `word - word` token sequences before processing. See current `app/Services/TextBlockService.php:138-170` and unique-word filtering at `:430-442`.
Difference classification: New unrelated bug
Evidence: `tests/Feature/TextBlockServiceTest.php:22-27` preserves valid hyphenated words and Unicode words; `:30-66` verifies hyphenated reader words and unique words; phrase coverage starts at `:68`.
Recommended action: Keep current approach but fix regression
Risk: High
Tests needed: Unit, Backend feature
Fix status: Already applied before this reconciliation; verified by evidence and tests
Notes: Correct behavior is stricter than old code and better specified. Do not restore old permissive behavior.

## REG-003: Vocabulary book filter returns stale/incorrect results

Status: Verified
Area: Vocabulary
Observed current behavior: Selecting different books reportedly returned the same words, likely around duplicate word text or stale chapter metadata.
Old behavior: Old `buildSearchRequest` filtered chapters by `book_id`, loaded processed text for phrase ids, and filtered words by `unique_words` text. Duplicate encountered-word rows with the same `word` text across books could not be disambiguated. See old `app/Services/VocabularyService.php:566-656`.
Current implementation: Current `buildSearchRequest` filters processed chapters by book/chapter, collects `unique_words`, `unique_word_ids`, and `unique_phrase_ids`, validates word ids against text, uses ids when valid, and falls back to text when migrated chapter ids are missing/stale. See current `app/Services/VocabularyService.php:592-706`.
Difference classification: Intentional performance refactor with regression
Evidence: Current optimized chapter list loading in `app/Services/VocabularyService.php:386-418`. Regression tests cover duplicate text by id in `tests/Feature/VocabularyTest.php:191-266`, missing/empty ids in `:268-285`, stale/mismatched ids in `:287-303`, and chapter filter fallback in `:305-321`.
Recommended action: Keep current approach but fix regression
Risk: High
Tests needed: Backend feature, Component/E2E
Fix status: Applied and verified on 2026-06-18.
Notes: Current frontend sends `book` and resets `chapter` on book change in `resources/js/components/Vocabulary/Vocabulary.vue:376-390` and `:451-478`. Follow-up investigation on 2026-06-18 identified two remaining risks: `loadVocabularySearchPage` has no latest-request guard, so an older response can overwrite a newer book selection; and `buildSearchRequest` chooses ID filtering when all collected IDs validate, which can omit words from partially migrated books where some chapters have IDs and other chapters only have `unique_words`.

Planned failing tests before the follow-up fix:

- Backend feature: two books with distinct words and phrases; Book A, Book B, and Any must return distinct expected sets.
- Backend feature: a partially migrated book containing one chapter with valid `unique_word_ids` and one chapter with only `unique_words` must return both chapters vocabulary.
- Frontend/static contract: Vocabulary requests must use a monotonically increasing request sequence and ignore stale success/error callbacks.

Follow-up result on 2026-06-18:

- Root cause confirmed: ID filtering was enabled when collected IDs were valid even if they did not cover words from partially migrated chapters.
- Root cause confirmed: Vocabulary had no latest-request guard, allowing an older response to overwrite a newer book selection.
- Phrase compatibility: chapters with `unique_phrase_ids = null` now fall back to processed-text phrase IDs; normally migrated chapters keep the indexed fast path.
- Phrase fallback contract: the fallback reads the phrase IDs already embedded in that filtered chapter's compressed `processed_text`; it does not match phrases by text. Phrase lookup remains scoped by user and language, so equal phrase text in different books cannot collide through this path. A wrong association would require already-corrupt/stale phrase IDs inside the chapter's own processed text.
- Phrase fallback lifecycle: this is a temporary legacy-data compatibility layer for chapters missed by or predating the `unique_phrase_ids` backfill. Current chapter processing calls `refreshUniquePhraseIds()`, so newly processed chapters should not use it. The null-only fallback can issue one extra query per legacy chapter; retain it until production data is backfilled/verified, then remove it with a dedicated migration or repair command.
- Changed files: `app/Services/VocabularyService.php`, `resources/js/components/Vocabulary/Vocabulary.vue`, `tests/Feature/VocabularyTest.php`, and `tests/Feature/VueMigrationStaticTest.php`.
- Verified tests: distinct Book A/Book B/Any words and phrases; partial word-ID migration; missing phrase-ID fallback; stale frontend success/error guards.
- Mounted stale-response coverage added on 2026-06-20:
  - `tests/frontend/Vocabulary.spec.js` mounts the real `Vocabulary.vue` component with lightweight Vuetify/dialog stubs and deferred Axios promises.
  - The first request uses Book A (`101`, representing Silo); before it resolves, the component starts Book B (`202`, representing Candela Obscure).
  - Resolving Book B first renders and stores only `candela-word`; resolving the older Book A success afterward cannot replace the words, active book filter, rendered text, or completed loading state.
  - A second test resolves Book B successfully and rejects Book A afterward; the stale error does not log the current-request error, clear the valid words, change the active filter, or restart loading.
  - Test command: `npm run test:frontend -- --reporter=verbose`; result: `1` file, `2` tests passed.
  - Test harness files: `vitest.config.mjs`, `tests/frontend/setup.js`, and the `test:frontend` package script. Dev-only dependencies are Vitest, Vue Test Utils, and jsdom.
  - Production code changed: no. The existing request-sequence implementation passed as written.
  - Regression verification: focused Vocabulary/Chapter/static PHP suite `37 tests, 625 assertions`; full PHPUnit `74 tests, 942 assertions`; `npm run check:migration` including production build; `npm run check:css` with zero errors and existing warnings only; and `git diff --check`.
- Remaining risk: text fallback for partially migrated word metadata preserves old compatibility behavior and can still be ambiguous when duplicate `encountered_words.word` rows exist; complete ID backfill remains the durable resolution.

## REG-004: Chapter pagination, items-per-page, and statistics

Status: Verified
Area: Library / Chapters
Observed current behavior: Total chapter count was wrong/equal to selected page size; `All` could send invalid `perPage`; items-per-page menu positioning was wrong; chapter statistics after later pages or beyond 50 chapters could be missing.
Old behavior: Old `BookChapters.vue` fetched all chapters, used `:items-per-page="-1"`, hid the footer, and then called `/chapters/word-counts/{bookId}` after load. Old backend returned all chapters and no pagination metadata. See old `resources/js/components/Library/BookChapters.vue:41-55`, `:304-327`; old `app/Services/ChapterService.php:25-62`.
Current implementation: Current `BookChapters.vue` uses `v-data-table-server`, `:items-length="totalChapters"`, explicit `itemsPerPageOptions` with `{ value: -1, title: 'All' }`, sends `all=true` instead of invalid `perPage=-1`, and uses a custom footer select `locationStrategy`. Backend returns `currentPage`, `lastPage`, `perPage`, and `total`, and computes counts only for visible/all returned chapters. See current `resources/js/components/Library/BookChapters.vue:62-79`, `:275-295`, `:346-395`, `:449-487`; current `app/Services/ChapterService.php:25-88`, `:133-170`.
Difference classification: Intentional performance refactor with regression
Evidence: Backend tests validate invalid `perPage`, pagination totals, all-mode, and large book statistics in `tests/Feature/ChapterTest.php:26-110` and `:194-288`. Static overlay tests validate footer menu positioning in `tests/Feature/VueMigrationStaticTest.php:163-181`.
Recommended action: Keep current approach but fix regression
Risk: High
Tests needed: Backend feature, Component/E2E, Visual regression
Fix status: Verified on 2026-06-18; no additional production backend change was required.
Notes: Correctness-preserving performance approach is current server pagination plus explicit all-mode; do not revert to loading every chapter by default. Follow-up verification on 2026-06-18 strengthened the contract tests for page sizes 10/25/50, stable totals on page 2, complete numeric statistics beyond chapter 50, numeric zero values, explicit `all=true` without `perPage=-1`, controlled validation errors, API-total mapping, stale-response protection, and a distinct error empty-state.

Planned failing tests before the follow-up fix:

- Backend feature: all five statistics fields must exist with numeric values on chapters beyond 50, including an all-zero processed chapter.
- Frontend/static contract: chapter requests must map `response.data.total`, use `all=true` without invalid `perPage`, ignore stale responses, and render request errors as an error state rather than `No data available`.

Follow-up result on 2026-06-18:

- Changed files: `tests/Feature/ChapterTest.php` and `tests/Feature/VueMigrationStaticTest.php`.
- Backend feature coverage now asserts page sizes 10/25/50, stable total 83, page 2 rows and total, all five numeric statistics on every returned chapter beyond 50, legitimate zero values, `all=true`, and controlled rejection of `perPage=-1`.
- Frontend contract coverage asserts API `total` mapping, latest-request protection, explicit All request behavior, and a distinct request-error empty state.
- Browser verification on isolated data confirmed 1-10, 1-25, 1-50, page 2 as 51-83, and All as 1-83 of 83. Chapter 83 displayed Total 0, Unique 1, Known 0, Highlighted 0, New 1. No API error or false No data state appeared.

## REG-005: Table/list action buttons oversized or inconsistent

Status: Verified
Area: Admin / Vocabulary / Library
Observed current behavior: Edit/delete buttons became huge in multiple tables and action columns became too wide.
Old behavior: Old components used bare Vuetify 2 `<v-btn icon>` and compact action column widths, for example old Admin Dictionaries action buttons and `width: '110px'` at `resources/js/components/Admin/AdminDictionarySettings.vue:90-108` and `:168-172`.
Current implementation: Current components add shared `table-action-button`, with a 96px action column in Admin Dictionaries and 32px circular button dimensions in `resources/sass/app.scss`. See current `resources/js/components/Admin/AdminDictionarySettings.vue:90-110`, `:169-174`; `resources/js/components/Vocabulary/Vocabulary.vue:249-269`; `resources/sass/app.scss:201-221`.
Difference classification: Incorrect Vuetify 2 -> Vuetify 3 migration
Evidence: Static regression checks require `table-action-button` in Admin Users/Dictionaries/Fonts and Vocabulary, and require the shared CSS in `tests/Feature/VueMigrationStaticTest.php:188-204`.
Recommended action: Port old approach correctly to current framework
Risk: Medium
Tests needed: Component, Visual regression, Static regression
Fix status: Applied and verified on 2026-06-18
Notes: The shared 32px icon-button contract now covers Admin Users, Dictionaries, Fonts, Vocabulary, Book Chapters, and Book List.

Follow-up investigation on 2026-06-18:

- Confirmed shared root cause: the later global `div/button/a.v-btn` padding rule excludes only `.toolbar-button` and `.stage-button`. Its more-specific `padding-left/right: 18px !important` can override `.table-action-button` compact padding, so the shared class is not actually authoritative.
- Book Chapters and Book List still use local `size`/`density` or bare icon-button configuration instead of the shared dense-table action contract.
- Applied fix: exempted `.table-action-button` and `.vertical-toolbar-button` from the global text-button padding rule, made the 32px geometry non-shrinking, and migrated Book Chapters and Book List actions to the shared class and 96px column.
- Changed files: `resources/sass/app.scss`, `resources/js/components/Library/BookChapters.vue`, `resources/sass/Library/BookChapters.scss`, `resources/js/components/Library/BookListLayout/BookListTable.vue`, and `tests/Feature/VueMigrationStaticTest.php`.
- Automated coverage now requires the shared class on all representative tables, 32x32 fixed geometry, global-padding exclusions, and 96px action columns.
- Isolated browser verification in light and dark themes measured every representative action at 32x32 with zero padding. Verified Admin Users, Dictionaries, Fonts, Vocabulary, Book List, and Book Chapters; icons remained visible in both themes.

## REG-006: Reader and Review toolbar buttons oval/overlapping/low contrast

Status: Verified
Area: Reader / Review
Observed current behavior: Vertical toolbar buttons became oval, spacing inconsistent, possible overlap/contrast issues.
Old behavior: Old Reader toolbar used bare Vuetify 2 icon buttons in a 44px rail; Review similarly used bare icon buttons. See old `resources/js/components/TextReader/TextReader.vue:10-20`, old `resources/sass/TextReader/TextReader.scss:49-69`, and old `resources/js/components/Review/Review.vue:107-145`.
Current implementation: Current Reader and Review toolbar buttons use shared `vertical-toolbar-button`; Reader CSS reserves a 52px rail and applies explicit 40x40 circular toolbar-button dimensions and theme colors. See current `resources/js/components/TextReader/TextReader.vue:17-26`, `resources/js/components/Review/Review.vue:107-149`, `resources/sass/app.scss:238-254`, and `resources/sass/TextReader/TextReader.scss:420-464`.
Difference classification: Incorrect Vuetify 2 -> Vuetify 3 migration
Evidence: Static checks require `vertical-toolbar-button` in Reader and Review plus shared CSS in `tests/Feature/VueMigrationStaticTest.php:201-208`.
Recommended action: Port old approach correctly to current framework
Risk: Medium
Tests needed: Component, Visual regression, Static regression
Fix status: Applied and verified on 2026-06-18
Notes: Browser verification remains part of the acceptance evidence because static tests cannot prove computed geometry or panel separation.

Follow-up investigation on 2026-06-18:

- Confirmed shared root cause: Reader buttons carry both `.toolbar-button` and `.vertical-toolbar-button`, while Review buttons carry only `.vertical-toolbar-button`. The global text-button padding rule therefore overrides Review's zero padding and can stretch the rendered icon button.
- Applied fix: treated `.vertical-toolbar-button` as a shared icon-button exemption and fixed width, height, flex basis, aspect ratio, padding, and border radius in `resources/sass/app.scss`.
- Automated coverage requires the shared class in Reader and Review and asserts the 40x40 circular contract.
- Isolated browser verification measured all eight Reader and seven Review controls at 40x40, zero padding, and 50% radius at 1440px desktop and 900px tablet widths.
- Reader verification with the details panel open showed an 8px separation between the panel and toolbar rail; no overlap or viewport overflow was observed. Dark-theme toolbar background/icon colors computed as `rgb(40, 39, 44)` and `rgb(226, 225, 232)`.

## REG-007: Dialog height, internal scroll, and edit chip text

Status: Verified
Area: Modal / Admin / Vocabulary / Reader settings
Observed current behavior: Some dialogs showed unnecessary internal scroll; Word/Phrase edit modals had unreadable brown chips; Reader settings controls could overlap.
Old behavior: Old dialogs often used fixed `height="300px"` or `scrollable`, and chips used Vuetify 2-only props such as `dark`, `small`, and icon `right`. See old Admin edit user at `resources/js/components/Admin/AdminEditUserDialog.vue:1-25`; old Vocabulary edit chips at `resources/js/components/Vocabulary/VocabularyEditDialog.vue:24-67`; old Vocabulary import scrollable dialog at `resources/js/components/Vocabulary/VocabularyImportDialog.vue:1-27`.
Current implementation: Current dialogs use `model-value`/`update:model-value`, `app-dialog-card`, viewport max height, and flex card text/actions; Vocabulary edit chips use Vuetify 3 `:size` and normal icon spacing. See current `resources/js/components/Admin/AdminEditUserDialog.vue:1-25`, current `resources/js/components/Vocabulary/VocabularyEditDialog.vue:1-63`, current `resources/js/components/Vocabulary/VocabularyImportDialog.vue:1-27`, and shared `.app-dialog-card` in `resources/sass/app.scss:223-236`.
Difference classification: Incorrect Vuetify 2 -> Vuetify 3 migration
Evidence: Static checks prevent fixed-height dialog regressions and require app-dialog-card in `tests/Feature/VueMigrationStaticTest.php:219-231`; shared UI pattern checks at `:188-208` also cover dialog/toolbar/action classes.
Recommended action: Port old approach correctly to current framework
Risk: Medium
Tests needed: Component, Visual regression, Static regression
Fix status: Applied and verified on 2026-06-18
Notes: Old fixed heights are not a behavior to restore. Shared dialog sizing, Vocabulary edit chips, and the deferred Reader Settings alignment follow-up are now verified.

Follow-up investigation on 2026-06-18:

- Current `app-dialog-card` is directionally correct, but the test only checks that the class name exists. It does not assert dynamic viewport sizing, `min-height: 0` on the flex body, or footer separation.
- Vocabulary edit chips render literal default-slot labels and use the semantic `on-primary` token, but tests do not verify those labels or the contrast contract.
- Runtime inspection found an additional migration defect: the shared card body/footer rules targeted Vuetify 2 internals `.v-card__text` and `.v-card__actions`, but Vuetify 3 renders `.v-card-text` and `.v-card-actions`.
- Applied dialog fix in `resources/sass/app.scss`: `100dvh`-aware card bounds, non-shrinking title/actions, a `min-height: 0` scrollable Vuetify 3 card body, and a separated footer. Mobile uses a smaller viewport inset while preserving body-only scrolling.
- Applied chip fix in `resources/js/components/Vocabulary/VocabularyEditDialog.vue` and `resources/sass/Vocabulary/VocabularyEditDialog.scss`: explicit flat primary chips, literal default-slot labels, Vuetify 3 append icons, and semantic `primary`/`on-primary` colors for content and icons.
- Automated coverage in `tests/Feature/VueMigrationStaticTest.php` rejects the obsolete Vuetify 2 card selectors, asserts the dialog flex/overflow contract, and requires all four metadata labels plus the semantic chip contrast contract.
- Isolated browser verification at 1440x1000 showed no body overflow for Edit Font (734/734px) or Upload Font (796/796px, 28 language checkboxes); Edit User also fit without overflow at desktop size. At 390x844, Upload Font scrolled only its body (673/796px) and kept footer actions visible.
- Phrase edit rendered `Added on`, `Due on`, and `0 lookups`; Word edit rendered `Finished review` and `1 lookups`. Labels and icons were visible in light theme (white on `rgb(171, 136, 117)`) and dark theme (`rgb(20, 17, 16)` on `rgb(197, 148, 125)`).
- Verification: full PHPUnit suite `69 tests, 869 assertions`; `npm run check:migration`; `npm run check:css` with zero errors and existing warnings only; production build; and `git diff --check`.

Reader Settings alignment investigation on 2026-06-18:

- Old implementation reference: `/data/git/old_LinguaCafe/LinguaCafe/resources/js/components/TextReader/TextReaderSettings.vue` used Vuetify 2 `v-row`/`v-col`, fixed 50px switch rows and 60px slider rows, 38px slider thumbs, `@change`, and menu activators using `{ on, attrs }`. Help icons were placed in the wide control column immediately before the switch. Old `resources/sass/TextReader/TextReader.scss` supplied only fixed row heights; old global `resources/sass/app.scss` positioned slider labels with a Vuetify 2-specific transform.
- Current implementation reference: `resources/js/components/TextReader/TextReaderSettings.vue` correctly migrated model events and activator bindings, and moved help icons next to their labels. It retained mixed Bootstrap-like column proportions (`8/4`, `md=4/8`, slider labels at `sm=3`), per-row padding/justify utilities, and no common semantic row/control contract. The result is an excessively wide control column, switches pinned to its far edge, and inconsistent label/control boundaries across tabs.
- Current Reader-specific styles in `resources/sass/TextReader/TextReader.scss` partially compensate with minimum heights, `:has(.v-switch)` mobile rules, and slider offsets, but still target obsolete Vuetify 2 `.v-card__text`. They also layer a Reader-specific thumb-label transform over the global Vuetify 3 slider transform in `resources/sass/app.scss`.
- Classification: combination of incorrect Vuetify 2 -> Vuetify 3 migration, lost shared row structure, invalid/inconsistent flex-grid layout, and slider-label positioning overrides.
- Affected Text tab controls: font type, line spacing, maximum text width, font size, all highlight/furigana switches, auto move/highlight/level-up help rows, subtitle timestamp/spacing, TTS voice, and TTS speed.
- Affected Vocabulary box controls: scroll method, vocabulary sidebar help/switch, and vocabulary bottom-sheet help/switch.
- Affected Vocabulary hover box controls: hover-box help/switch, dictionary-search switch, hover delay slider, and preferred-position select.
- Existing coverage only checks generic dialog sizing and absence of fixed heights in `tests/Feature/VueMigrationStaticTest.php`; it does not assert semantic Reader Settings rows, Vuetify 3 card selectors, slider/thumb containment, help activator placement, aligned control columns, model bindings, or responsive overflow.
- Planned fix: introduce one Reader Settings row/label/control class contract across all three tabs, keep help activators inside the label group, contain sliders and their labels in the control column, align switches at the start of a bounded control column, and stack rows below the mobile breakpoint without absolute positioning.

Reader Settings alignment fix and verification on 2026-06-18:

- Changed `resources/js/components/TextReader/TextReaderSettings.vue` to use 23 semantic `settings-row` instances with select, slider, and toggle variants. Every row now has a logical label group and bounded control group; six help controls remain beside their labels.
- Changed `resources/sass/TextReader/TextReader.scss` to use a component-scoped two-column grid (`180-260px` label plus a control column capped at 560px), a compact mobile toggle grid, stacked mobile select/slider rows, and Vuetify 3 `.v-card-text` and `.v-slider-thumb__label` selectors.
- Root cause confirmed at runtime: Vuetify 3 renders `.v-slider-thumb__label`, while the migrated Reader override still targeted Vuetify 2 `.v-slider__thumb-label`. The obsolete transform shifted no real Vuetify 3 value element; the later attempted vertical transform moved the actual value outside its row. The final contract uses Vuetify's native vertical placement and only centers the label horizontally.
- Slider thumbs are intentionally 20x20. Sliders and switches use `hide-details` so Vuetify 3 does not reserve empty message space. Stored setting names, values, `v-model` bindings, and `@update:model-value="saveSettings"` behavior are unchanged.
- Help activators are Vuetify 3 icon buttons with explicit accessible names. Browser verification confirmed Enter opens the explanatory popover; the hover-box help card measured 320x106 and remained inside the 390px viewport.
- Mobile tabs enable Vuetify arrows at `smAndDown`; the 390px viewport displayed Text and Vocabulary box plus a visible next arrow to reach Vocabulary hover box without document-level horizontal overflow.
- Static coverage in `tests/Feature/VueMigrationStaticTest.php::test_reader_settings_use_a_shared_responsive_control_row_contract` asserts row variants, labels/controls/help buttons, responsive grid rules, current Vuetify selectors, `hide-details`, update bindings, mobile tab arrows, and absence of `:has(.v-switch)` and obsolete Reader slider/card selectors.
- Isolated browser verification:
  - 1440x1000 light: all visible slider value boxes were contained by their own rows; slider tracks were 560px; all Text-tab switches shared x=525; no horizontal overflow.
  - 900x768 light: slider value remained contained, track width was 514px, switch x positions remained aligned, and the active tab body required no unnecessary internal scrolling.
  - 390x844 light/dark: document scroll width remained 390px on Text, Vocabulary box, and Vocabulary hover box; rows reported no horizontal overflow; slider value boxes remained inside their rows; footer actions remained visible while only the modal body scrolled.
  - Dark theme computed card colors were `rgb(40, 39, 44)` / `rgb(236, 236, 241)`; help, switch, and slider-value text remained readable.
  - Keyboard/update behavior: hover-delay slider changed from 300 to 400 with ArrowRight; the hover-box switch changed through its native checkbox control; help opened with Enter.
- Verification: full PHPUnit suite `70 tests, 902 assertions`; focused `VueMigrationStaticTest` `12 tests, 105 assertions`; `npm run check:migration`; production build; `npm run check:css` with zero errors and existing warnings only; and `git diff --check`.

## REG-008: Dark theme contrast regressions

Status: Verified for three shared clusters; remaining families deferred
Area: Theme
Observed current behavior: Dark-on-dark text/icons appear in several places, including fonts/admin pages and toolbars/action icons.
Old behavior: Old global CSS targeted Vuetify 2 theme classes and variables such as `.theme--light`, `.v-menu__content`, `var(--v-foreground-base)`, `var(--v-text-base)`, and active list item icon colors. See old `resources/sass/app.scss:123-152`, `:171-205`.
Current implementation: Current theme tokens live in `resources/js/themes.js` and migrated CSS uses `rgb(var(--v-theme-*));`, with broad overlay/list/form overrides in `resources/sass/app.scss`. Current dark palette defines explicit `text`, `textSecondary`, `icon`, `foreground`, `background`, and `primary` colors in `resources/js/themes.js:41-70`.
Difference classification: Incorrect Vuetify 2 -> Vuetify 3 migration
Evidence: Search still finds hardcoded colors and many theme token overrides in current `resources/sass/app.scss`; some are intentional white-on-primary or light input fixes, so this needs contrast-focused review rather than blind replacement.
Recommended action: Create separate performance/product decision
Risk: Medium
Tests needed: Component, Visual regression, Static/CSS contrast audit
Fix status: First shared table/heading/pagination cluster applied and verified on 2026-06-18
Notes: This does not close the full dark-theme audit. Forms, general cards/menus, tabs/navigation, calendar-specific overrides, and non-table feature components remain separate focused batches.

Structured dark-theme audit on 2026-06-18:

- Theme/config comparison:
  - Old Vuetify 2 used flat theme objects in `resources/js/themes.js`, exposed colors as `--v-*-base`, and switched `vuetifyHandler.theme.dark`. Old `resources/sass/DarkMode.scss` explicitly targeted `.theme--dark`, `.v-data-table__wrapper`, `.v-pagination__item`, `.v-pagination__navigation`, `.v-card__text`, and `.v-menu__content`.
  - Current Vuetify 3 uses `createVuetify()` in `resources/js/vuetify.js`, nested `{ dark, colors, variables }` theme definitions through `ThemeService`, and `rgb(var(--v-theme-*));` tokens. The dark palette now includes semantic `surface`, `surfaceElevated`, `inputSurface`, `border`, `divider`, `hoverSurface`, `selectedSurface`, `focusIndicator`, text, icon, disabled, and `on-primary` tokens.
  - Token contrast is not the primary defect. Current dark ratios include text/surface 12.59:1, secondary/elevated surface 8.92:1, disabled/foreground 4.54:1, icon/foreground 11.41:1, border/foreground 3.16:1, and on-primary/primary 7.08:1. `divider` is intentionally subtle at 1.59:1 and must not be used as the only boundary for essential controls.
- Typography/headings inventory:
  - Shared `.subheader` has a hardcoded `#2d2d2d` in `resources/sass/app.scss`, classified as a hardcoded component override. A later dark selector attempts to replace it with the semantic text token.
  - Vuetify heading classes and dialog titles mostly inherit the theme correctly, but current shared dark coverage does not explicitly group `.subheader`, `.text-h1` through `.text-h6`, and table-page titles under one current-framework contract. Classification: hardcoded override plus incomplete migration; safe to include in the first shared cluster.
- Tables and pagination inventory:
  - Old table/pagination rules target Vuetify 2 DOM and cannot match Vuetify 3 reliably. Current `DarkMode.scss` still retains those obsolete rules near the top, then adds a newer Vuetify 3 baseline later.
  - The newer table baseline uses semantic tokens for surfaces, headings, row text, borders, hover, footer, and pagination. However, active/disabled pagination selectors and focus behavior need DOM verification, and table-action icon inheritance can be overridden by competing generic icon rules.
  - `resources/sass/app.scss` also retains obsolete `.v-data-table__wrapper` selectors for font size, hover, and row-line behavior. Classification: incomplete Vuetify 2 -> Vuetify 3 migration.
- Icon-button inventory:
  - Shared dark rules map ordinary icons to `icon` and disabled icons to `iconDisabled`; primary buttons use `on-primary`. This is directionally correct.
  - Error/success icon exceptions and generic `.v-data-table .v-icon` rules compete by specificity/order. Classification: shared token usage with unclear cascade; table action icons require representative browser confirmation before any change.
- Form controls inventory:
  - Current shared `.v-field` rules use semantic input, border, focus, error, and disabled tokens. Several older `.v-select__selections`, `.v-card__text`, and autofill overrides remain. Classification: incomplete migration, but deferred because this first batch is tables/headings/pagination only.
- Cards/dialogs/menus inventory:
  - Current shared surfaces and tooltip tokens are semantic, while older rules still target `.v-card__text`, `.v-card__actions`, and `.v-menu__content`. Classification: incomplete migration. Dialog sizing is protected; only contrast will be observed in this task, not broadly rewritten.
- Tabs/navigation inventory:
  - Current shared tab tokens are semantic, but `resources/sass/app.scss` still contains hardcoded white selected-tab/navigation text. Classification: hardcoded override; deferred to a later focused batch unless runtime proves it directly affects the selected table cluster.
- Reader/Review inventory:
  - Toolbar controls already use semantic surface/icon/border tokens in the current shared baseline. Geometry is protected. Classification: current shared token implementation; verify for regression only and do not modify in this batch.
- First safe implementation cluster selected: shared page headings plus Vuetify 3 tables/data tables, table action icons, table borders/dividers, and pagination states. Representative screens are Admin Fonts, Admin Users or Dictionaries, and Vocabulary. Forms, general cards/menus, tabs/navigation, calendar, and Reader/Review remain deferred.
- Planned safeguards: use only semantic theme tokens, avoid page-specific colors, preserve 32x32 table-action geometry, add static checks for current Vuetify 3 table/pagination selectors and forbidden hardcoded shared-cluster colors, then verify computed colors and state distinctions in an isolated runtime before finalizing the batch.

First-cluster pre-fix browser evidence:

- Admin Fonts dark theme: heading text was 12.59:1 or better, table header text 8.92:1, row text 12.59:1, ordinary edit icon 11.41:1, and the outer table/header border used the 3.16:1 `border` token. These parts need regression coverage, not recoloring.
- Admin Dictionaries dark theme exposed the shared action-icon cascade defect: a delete button used the error background `rgb(255, 79, 85)`, but generic `.v-data-table .v-icon` styling forced the normal icon token `rgb(226, 225, 232)`, yielding only 2.49:1. The button's default white foreground was 3.23:1. A semantic `on-error` foreground is required for this palette.
- Vocabulary pagination exposed the current Vuetify 3 state mismatch: the active button is identified by `aria-current="true"` inside `.v-pagination__item--is-active`. The shared rule set its primary background, but a later generic button rule restored normal text color. Computed active-page contrast was 2.26:1, while inactive pages were 11.26:1 and the disabled previous control was 4.06:1.
- Table row dividers currently use `divider` at 1.59:1 against the dark surface. This is acceptable for decorative separation but too weak when it is the only row boundary in dense data tables. The first cluster will use the existing 3.16:1 `border` token for table row boundaries without changing geometry.
- Root cause classification for this batch: shared semantic tokens exist, but incomplete Vuetify 3 state selectors and competing generic icon/button rules apply the wrong foreground tokens to primary pagination and error actions.

First shared dark-theme cluster fix and verification:

- Changed `resources/js/themes.js`: added semantic `on-error` values for light, dark, and eink palettes. Dark error foreground `#141110` on `#FF4F55` is 5.82:1; light uses the same semantic foreground and remains readable.
- Changed `resources/sass/DarkMode.scss`:
  - grouped shared headings (`.subheader`, `.text-h1` through `.text-h6`) under the dark text token;
  - changed dense table row boundaries from decorative `divider` to the 3.16:1 `border` token;
  - forced error table actions and icons to use `on-error`;
  - added current Vuetify 3 active pagination selectors for `.v-pagination__item--is-active` and `aria-current="true"`, including button content and icons, after competing generic button rules.
- Changed `scripts/check-dark-theme-contrast.js`: requires `on-error`, validates `on-error`/`error` at 4.5:1, requires current active-pagination and error-action selectors, and verifies data-table rows use the shared border token.
- Browser verification in an isolated runtime:
  - Admin Fonts, 1440x1000 dark: heading 12.59:1 or better, table header 8.92:1, row text 12.59:1, edit icon 11.41:1, table/header borders 3.16:1. Footer labels/arrows and disabled controls remained distinguishable.
  - Admin Dictionaries dark: edit icon remained 11.41:1; delete button and icon improved from 3.23:1/2.49:1 to 5.82:1. Both remained 32x32.
  - Vocabulary dark: table header 8.92:1, row text 12.59:1, edit action 11.41:1, row boundary 3.16:1. Active pagination improved from 2.26:1 to 7.08:1; inactive page was 11.26:1; disabled previous control was 4.06:1.
  - Pagination hover used the Vuetify overlay state, and keyboard focus produced a 2px `focusIndicator` outline with 2px offset. A focused table action retained its 32x32 geometry.
  - Edit Font dialog dark: card, title, body, actions, and button text used semantic dark text/surface colors; body remained 734/734px with no unnecessary scrolling, preserving REG-007.
  - Vocabulary at 390x844 dark: table text, row boundaries, badges, and actions remained readable and 32x32. Existing document-width overflow from the compact mobile navigation/pagination composition was observed but not changed because it is a layout issue outside this contrast-only batch.
  - Light theme recheck: headings, table text, pagination, and actions remained readable; table actions stayed 32x32. The semantic light error foreground rendered dark text/icon on the red action background.
- Automated verification: full PHPUnit suite `70 tests, 902 assertions`; `npm run check:migration`; production build; `npm run check:css` with zero errors and existing warnings only; `node scripts/check-dark-theme-contrast.js`; `node --check scripts/check-dark-theme-contrast.js`; and `git diff --check`.
- Deferred dark-theme clusters:
  - form controls and legacy select/autofill selectors;
  - cards, menus, overlays, and obsolete `.v-card__*`/`.v-menu__content` rules outside verified dialogs;
  - tabs and navigation hardcoded white active states;
  - calendar and date-picker component-specific colors;
  - Review animation/state colors and other feature-specific overrides;
  - broad removal of obsolete Vuetify 2 selectors, which requires separate visual batches rather than blind deletion.

Second REG-008 cluster audit on 2026-06-19: forms, selects, cards, menus, and help overlays

- Old implementation reference:
  - Vuetify 2 form/select contrast depended on `.theme--dark.v-select .v-select__selections`, `.v-input__control`, `.v-input__slot`, and WebKit autofill overrides in old `resources/sass/DarkMode.scss`.
  - Cards and menus used `.theme--dark.v-card > .v-card__text`, `.v-card__actions`, `.v-menu__content.theme--dark`, and `.v-menu__content .theme--dark.v-list-item`. Old active menu items forced primary backgrounds and light icon/text.
  - These selectors describe Vuetify 2 DOM and cannot be copied into the current app.
- Current theme/token implementation:
  - `resources/js/themes.js` already provides semantic `surface`, `surfaceElevated`, `inputSurface`, `border`, `hoverSurface`, `selectedSurface`, `focusIndicator`, `text`, `textSecondary`, `textMuted`, `textDisabled`, `icon`, and `iconDisabled` values.
  - Current shared rules near the end of `resources/sass/DarkMode.scss` target Vuetify 3 `.v-field`, `.v-field__input`, `.v-select__selection-text`, `.v-card-title`, `.v-card-text`, `.v-card-actions`, `.v-overlay__content .v-list`, `.v-list-item`, and tooltip overlay content.
  - Current `resources/sass/app.scss` also contains a framework-neutral surface/input normalization layer for `.v-card`, `.v-list`, `.v-field`, placeholders, autofill, overlay lists, hover, and active states.
- Obsolete Vuetify 2 selectors still present:
  - `resources/sass/DarkMode.scss`: `.v-select__selections`, `.v-card__subtitle`, `.v-card__text`, `.v-theme--dark.v-list-item` descendants used as if theme classes were attached to each old child, old picker body selectors, and old menu-item ancestry assumptions.
  - `resources/sass/app.scss`: `.v-card__subtitle`, `.v-card__title`, `.v-card__actions`, `.v-card__text`, `.v-data-table__wrapper`, and old light-theme child selectors.
  - Feature Sass includes old `.v-card__text` child selectors in Admin API settings. That page-specific debt is outside this representative cluster unless runtime evidence shows it is active.
  - Classification: incomplete Vuetify 2 -> Vuetify 3 migration. Obsolete selectors are not sufficient evidence of a visible defect because later current-framework rules may already win.
- Current selectors already correctly tokenized:
  - field surfaces, input/textarea text, labels, placeholders, outline/focus colors, error text, disabled text, ordinary card/dialog surfaces, card title/body text, overlay list surfaces, menu text, hover/selected surfaces, and tooltip surface/border all reference semantic theme tokens.
  - Classification: already correctly tokenized, pending computed-style verification.
- Risky cascade points requiring runtime evidence:
  - `.v-icon` and generic button rules may override field append/prepend icons and menu item icons;
  - active menu rules in `app.scss` use `gray3` rather than the newer semantic `selectedSurface`;
  - placeholder rules use the full text token plus opacity rather than `textMuted`;
  - disabled field rules set text color on `.v-field--disabled` but may not cover selection text, labels, affixes, or icons in current Vuetify 3 DOM;
  - tooltip styling assumes `.v-tooltip > .v-overlay__content`, while Vuetify teleports overlay content and may not preserve that ancestry.
  - Classification: incomplete migration or unclear cascade; browser confirmation required before changes.
- Representative runtime targets for this batch: Vocabulary search and filter menus, Vocabulary import dialog, Edit User or Edit Font dialog, Reader Settings selects and help menus, one open dropdown over a dark surface, and one help tooltip/popover. Protected dialog sizing, Reader Settings geometry, form bindings, and business logic will not be changed.
- Planned test strategy: first capture computed foreground/background/border colors and contrast for normal, placeholder, focused, disabled, active, hover, dialog, menu, and help-overlay states. Add focused static checks only for selectors/tokens proven necessary by that evidence; do not remove all legacy selectors blindly.

Second-cluster pre-fix browser evidence:

- Vocabulary filters/search, dark:
  - Search field effective filled surface used the current dark filled-field overlay, with text `rgb(236,236,241)` and border/outline visible against the dark surface.
  - Level and Order by menus rendered on `rgb(40,39,44)` with `rgb(236,236,241)` text. The active item used generic `gray3` (`rgb(75,74,81)`) and remained readable at 7.44:1, but it did not use the dedicated `selectedSurface` token.
  - Order-by icons matched menu text and were readable. No stale Vuetify 2 selector was required for the current menu DOM.
- Vocabulary import dialog, dark:
  - Card, title, body, actions, labels, file input text/icon, and delimiter text used `rgb(236,236,241)` on the dark surface and were readable.
  - The primary information alert used `rgb(20,17,16)` on `rgb(197,148,125)`, but its ordinary anchor inherited the application link color `rgb(158,158,255)`. That link measured only 1.11:1 against the alert surface. Root cause: generic anchor color overrides the semantic foreground already selected by the alert.
  - The disabled Import button retained its semantic primary/on-primary colors and Vuetify's disabled overlay; no geometry or behavior change is needed.
- Edit User dialog, dark:
  - Card, title, body, actions, text fields, labels, and switch label were readable. Dialog sizing remained unchanged.
- Reader Settings, dark:
  - Card and select value/icon text were readable; select menus used the same readable surface/text treatment as Vocabulary menus.
  - The disabled Vertical text switch had the same track `rgb(65,64,70)`, thumb `rgb(40,39,44)`, opacity, and label color as an enabled unchecked switch. Root cause: current CSS targets obsolete/missing `.v-field--disabled` paths but does not style Vuetify 3 `.v-selection-control--disabled`.
  - The help card rendered `rgb(236,236,241)` text on `rgb(40,39,44)` with a `rgb(118,114,125)` semantic border. The help activator icon was readable. This is a menu-backed help popover rather than a `v-tooltip`.
- Shared field/menu migration gaps:
  - Field outlines computed from the general text foreground instead of the dedicated `border`/`focusIndicator` tokens, despite the intended DarkMode rules. Current Vuetify field internals and later normalization rules require an explicit current-framework override.
  - Menu selected/hover behavior is readable but still driven by generic `gray3`/overlay opacity. This batch will move current overlay list states to `selectedSurface` and `hoverSurface` without changing menu geometry or selection behavior.
- Confirmed safe scope for implementation: semantic alert-link inheritance, current Vuetify 3 field border/focus selectors, disabled input/selection-control states, and current overlay list selected/hover states. Cards/dialog bodies and help-card surfaces already pass and need regression coverage rather than recoloring.

Second REG-008 cluster implementation and verification:

- Root cause: the newer semantic dark-theme baseline was scoped only under `#app .v-application.dark`. Vuetify 3 teleports dialogs, select menus, dropdowns, and help popovers into `.v-overlay-container`, outside that subtree. Current semantic rules therefore existed but did not reach the affected overlay content; older generic application/link/component rules won instead.
- Changed `resources/sass/DarkMode.scss`:
  - extended the shared dark baseline to `.v-overlay-container .v-theme--dark`, matching current Vuetify 3 teleported overlay roots;
  - made alert links inherit the alert's semantic foreground and retain an underline;
  - enforced semantic `border` and `focusIndicator` tokens on current `.v-field__outline` states;
  - added current `.v-input--disabled` coverage for field text, select values, labels, and icons;
  - added semantic disabled switch track/thumb styling and a disabled settings-row label state without changing Reader Settings geometry;
  - moved overlay list active and hover/focus states to `selectedSurface` and `hoverSurface`;
  - removed the overlapping obsolete global `.v-select__selections`, old card-child, and old overlay menu-item selectors.
- Changed `tests/Feature/VueMigrationStaticTest.php`: added `test_dark_forms_cards_and_menus_use_current_semantic_state_selectors`. It requires app-root and teleported-overlay coverage, semantic alert/field/disabled/menu selectors, and rejects the three removed Vuetify 2 selectors. The test failed before production changes and passes with 12 assertions.
- Changed `scripts/check-dark-theme-contrast.js`: requires teleported-overlay coverage and the second-cluster selectors, rejects the removed obsolete selectors, and verifies text on hover/selected surfaces plus focus indication on input surfaces.
- Post-fix browser evidence:
  - Vocabulary import alert link changed from 1.11:1 to 7.08:1 (`on-primary` on `primary`) while preserving same-tab navigation and underline.
  - Filled field text measured 10.78:1 on `inputSurface`; normal outline used `border`; focused outline and 2px focus ring used `focusIndicator` at 5.59:1 against the focused field surface.
  - Vocabulary Level menu active item used `selectedSurface` with 9.00:1 text contrast. Hover used `hoverSurface` with 9.70:1. Order-by icons remained readable and selection behavior was unchanged.
  - Reader Settings disabled Vertical text switch now differs from enabled unchecked switches: label/track/thumb use `textDisabled`/`iconDisabled`, with the disabled label retaining 4.54:1 against the dialog surface. Existing row alignment and switch geometry were unchanged.
  - Reader Settings select value and append icon remained readable. Its help popover retained `rgb(236,236,241)` text on `rgb(40,39,44)`, semantic border `rgb(118,114,125)`, and its existing 320x82 geometry.
  - Edit User dialog retained semantic card/title/body text, `inputSurface` fields, `border` outlines, 409/409px client/scroll height, and no document overflow.
  - Mobile 390x844 dark: Reader Settings dialog was 342px wide with zero document overflow; Vocabulary import dialog was 342px wide with zero document overflow. No safe-area, footer, or dialog-sizing rules changed.
  - Light theme recheck: Reader Settings card, select value/icon, and disabled control retained their existing light colors and zero overflow. The new rules are dark-theme scoped.
- Automated verification: focused static test `1 test, 12 assertions`; full PHPUnit suite `73 tests, 932 assertions`; `npm run check:migration` including production build; `npm run check:css` with zero errors and existing warnings only; direct dark contrast script and Node syntax checks; and `git diff --check`.
- Remaining REG-008 work is still deferred:
  - tabs/navigation hardcoded active-state white rules outside this cluster;
  - calendar/date-picker component-specific colors;
  - Review animation/state colors and broader Reader/Review feature verification;
  - feature-local obsolete card selectors such as Admin API settings, which need their own runtime evidence;
  - broad legacy selector cleanup outside the forms/selects/cards/menus families.

Third REG-008 cluster audit on 2026-06-20: tabs and navigation active states

- Old implementation reference:
  - Old Admin and Reader Settings tabs used Vuetify 2 `v-tabs`/`v-tab` markup, while old sidebar and bottom-navigation styles assumed white active text on the primary surface.
  - Old dark overrides targeted child-level Vuetify 2 classes such as `.theme--dark.v-list-item--active:before`, `.theme--dark.v-bottom-navigation .v-spacer`, and active navigation anchors. Those selectors do not describe the current Vuetify 3 DOM and are behavioral reference only.
- Current implementation:
  - Admin tabs are rendered by `resources/js/components/Admin/AdminSettingsLayout.vue`; Reader Settings tabs are rendered by `resources/js/components/TextReader/TextReaderSettings.vue`.
  - Main sidebar and mobile bottom navigation are rendered by `resources/js/components/Layout.vue`.
  - `resources/sass/DarkMode.scss` already defines semantic current-framework states using `selectedSurface`, `hoverSurface`, `focusIndicator`, `text`, and `on-primary`.
  - Later rules in `resources/sass/app.scss` override that semantic baseline: Admin selected tabs force `#ffffff` text/content/slider, active drawer items force `white`, and bottom navigation forces `#ffffff` text/icons/overlays on the primary surface.
- Classification:
  - Hardcoded color override: selected Admin tabs, active sidebar items, and mobile bottom navigation bypass the palette's semantic foreground.
  - Obsolete Vuetify 2 selectors: old child-level `.v-theme--dark` active-list and bottom-navigation selectors remain in `DarkMode.scss`; they are not a valid current active-state contract.
  - Already correct semantic token use: generic Reader Settings tabs and the newer shared dark hover/focus/selected rules already reference current Vuetify 3 classes and semantic tokens.
  - Runtime confirmation is required before removing or changing any rule because the later cascade may affect only specific component families.
- Expected root cause: the current dark primary is `#C5947D`, whose intended foreground is the configured `on-primary` `#141110`. Hardcoded white ignores that palette contract and is expected to produce insufficient selected-state contrast. Generic selected-surface tabs should remain `text` on `selectedSurface`.
- Exact controls requiring pre-fix evidence:
  - all seven Admin settings tabs;
  - all three Reader Settings tabs;
  - active, inactive, hovered, and keyboard-focused desktop sidebar items;
  - active, inactive, hovered, and keyboard-focused mobile bottom-navigation items;
  - mobile drawer navigation if it inherits the drawer active-item contract.
- Planned safeguards:
  - measure computed foreground/background/outline colors and contrast in dark and light themes before changing production Sass;
  - add a focused static regression test that rejects hardcoded white in shared active-state rules and requires `on-primary`, `selectedSurface`, `hoverSurface`, and `focusIndicator` as appropriate;
  - preserve routing, tab models, navigation geometry, and all previously verified component sizing.

Third-cluster pre-fix browser evidence:

- Admin Users at 1440x1000 dark: the selected tab background was the semantic `selectedSurface` `rgb(73,60,53)`, but later `app.scss` declarations forced its label/content/slider to white. Contrast remained readable at 10.60:1, so this was a hardcoded semantic-cascade defect rather than an immediate unreadable state. Inactive and hover states were already readable; keyboard focus used a 2px `focusIndicator` outline.
- Reader Settings at 1440x1000 dark: all three tabs already used the intended current-framework contract: selected `text` on `selectedSurface` at 9.00:1 and a `focusIndicator` slider. This component required verification only.
- Desktop Home sidebar dark: the active item background was `primary` `rgb(197,148,125)`. Parent and icon used `on-primary`, but the title was overridden to `text` `rgb(236,236,241)`, only 2.66:1 against primary. This confirmed a competing generic active-list child rule.
- Mobile Home at 390x844 dark: bottom navigation used white labels/icons on primary, only 2.66:1. The generic `.v-btn--active .v-btn__overlay { opacity: 0 !important; }` also suppressed the component's intended active overlay, making active and inactive items visually identical.
- Root cause classification: hardcoded foreground overrides plus an over-broad Vuetify 3 active-overlay reset. The generic semantic tokens and component markup were correct; the cascade applied the wrong foreground or erased the selected-state indicator.

Third REG-008 cluster implementation and verification:

- Changed `resources/sass/app.scss`:
  - replaced hardcoded white in Admin selected tabs, active drawer items, and mobile bottom navigation with `on-primary`;
  - explicitly applied `on-primary` to active drawer title/content/icon descendants so the generic active-list text rule cannot override them;
  - excluded bottom-navigation buttons from the generic active-overlay suppression;
  - restored semantic `on-primary` active/hover/focus overlays and added a 3px inset `on-primary` selection indicator plus 700 weight without changing navigation geometry.
- Changed `resources/sass/DarkMode.scss`:
  - made Admin selected tabs use `text` on `selectedSurface` and `focusIndicator` for the slider;
  - made current Vuetify 3 active drawer items consistently use `primary`/`on-primary`;
  - removed obsolete Vuetify 2 active-list pseudo-element, bottom-navigation spacer, and active-anchor icon selectors that no longer match the current DOM.
- Changed `tests/Feature/VueMigrationStaticTest.php`: added `test_tabs_and_navigation_active_states_use_semantic_theme_tokens`. It failed before the Sass fix and now verifies drawer descendants, Admin tabs, bottom-navigation foreground/indicator/overlay behavior, and absence of hardcoded white in this shared cluster.
- Changed `scripts/check-dark-theme-contrast.js`: now checks `app.scss` for the shared semantic tab/navigation contracts and rejects reintroduced hardcoded white active-state rules.
- Post-fix browser evidence:
  - all seven Admin tabs selected the correct route/model state. Dark selected tabs used `rgb(236,236,241)` on `rgb(73,60,53)` at 9.00:1 with `rgb(217,172,148)` slider; hover used `hoverSurface`; keyboard focus retained the 2px semantic outline.
  - all three Reader Settings tabs retained the same 9.00:1 selected state and semantic slider; tab switching and settings content were unchanged.
  - desktop and mobile-drawer active Home navigation used `rgb(20,17,16)` for label and icon on `rgb(197,148,125)`, improving the affected title from 2.66:1 to 7.08:1. Inactive text remained readable; hover used `hoverSurface`; keyboard focus used a 2px `focusIndicator` outline.
  - mobile bottom navigation at 390x844 and 900x900 used `on-primary` at 7.08:1. The active item had a 14% semantic overlay, 3px inset indicator, and 700 weight; inactive items had no overlay/indicator. Keyboard focus showed a 2px `focusIndicator` outline. Document client/scroll widths matched at both widths.
  - light theme retained its prior primary/white Admin tab, sidebar, and bottom-navigation appearance through the light `on-primary` token. No light-theme geometry or routing behavior changed. The existing light `on-primary`/primary text contrast is 3.23:1 and remains a palette-level follow-up outside this dark-only cluster.
- Automated verification: focused test `1 test, 10 assertions`; full PHPUnit suite `74 tests, 942 assertions`; `npm run check:migration` including production build; `npm run check:css` with zero errors and existing warnings only; direct dark-contrast and Node syntax checks; and `git diff --check`.
- Remaining REG-008 work stays deferred:
  - calendar/date-picker component-specific colors;
  - Review animation/state colors and broader Reader/Review feature verification;
  - feature-local obsolete selectors such as Admin API settings;
  - broad legacy selector cleanup and the separate light-palette `on-primary` contrast decision.

## REG-009: Sidebar/mobile bottom icon alignment

Status: Verified
Area: Sidebar / Mobile drawer
Observed current behavior: Hide, Theme, and Language bottom-control icons did not align with labels; mobile bottom area could be cramped.
Old behavior: Old large drawer bottom controls used inline `<v-icon>`/`<v-img>` followed by manual padding spans. Mini drawer used standalone rounded text buttons. See old `resources/js/components/Layout.vue:44-76`.
Current implementation: Current large drawer uses Vuetify 3 `#prepend` slots and `v-list-item-title`, with one `navigation-bottom-item` geometry contract shared by Hide, Theme, and Language. The mini drawer wraps controls and uses `navigation-flag` classes. See current `resources/js/components/Layout.vue:50-88`; supporting CSS is in `resources/sass/app.scss` bottom-navigation rules.
Difference classification: Incorrect Vuetify 2 -> Vuetify 3 migration
Evidence: Current markup uses the same structural pattern as main list items rather than manual span padding. `tests/Feature/VueMigrationStaticTest.php::test_sidebar_bottom_controls_share_one_vuetify_3_alignment_contract` protects the shared structure, geometry, and safe-area rule; desktop and mobile browser measurements are recorded below.
Recommended action: Port old approach correctly to current framework
Risk: Medium
Tests needed: Component, Visual regression, Static regression
Fix status: Applied and verified on 2026-06-19
Notes: Do not restore manual padding spans; use Vuetify 3 prepend/title layout.

Focused REG-009 investigation on 2026-06-19:

- Old implementation: `/data/git/old_LinguaCafe/LinguaCafe/resources/js/components/Layout.vue` rendered bottom controls as loose Vuetify 2 children (`v-icon`/`v-img` plus manually padded `span`). Hide and Theme used `pl-6`; Language used a different `pl-5`. The mini drawer used standalone text-style buttons. Old `resources/sass/app.scss` only supplied drawer widths and button geometry, so alignment depended on Vuetify 2 inline layout and manual padding.
- Current implementation: `resources/js/components/Layout.vue` correctly uses Vuetify 3 `#prepend` and `v-list-item-title`, but the three controls do not yet share one explicit component class. Hide/Theme rely on generic `.navigation-button`; Language adds `.navigation-language-button`, while bottom titles use a separate `.navigation-bottom-title`.
- Current CSS is duplicated in two `#navigation-drawer` blocks in `resources/sass/app.scss`. The first block defines bottom-list geometry and includes a Language-only `margin-left: 1px; margin-right: -1px` correction. The later block redefines prepend width, title line-height, bottom-title padding, and flag dimensions. Classification: incomplete Vuetify 2 -> Vuetify 3 migration plus duplicated shared CSS and a screenshot-style flag offset.
- The bottom title uses `padding-left: 24px`, while main sidebar titles use template utility class `pl-6`. These happen to be numerically similar but are owned by different contracts, so the bottom controls can drift from main navigation as Vuetify slot internals change.
- Safe-area handling exists only on `.v-navigation-drawer__append` as `padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px))`. There is no explicit minimum inset for the bottom-control list, and the mini stack has its own fixed `padding-bottom: 10px`. Classification: partial safe-area implementation that needs one shared append/list contract.
- Affected files: `resources/js/components/Layout.vue`, `resources/sass/app.scss`, `tests/Feature/VueMigrationStaticTest.php`, and this tracker.
- Exact affected controls: expanded Hide, Theme, Language rows; collapsed Expand/Theme/Language icon buttons; mobile temporary drawer expanded rows. Navigation, theme selection, language selection, and collapse/expand behavior are not part of the fix.
- Existing tests only reject the obsolete `.v-navigation-drawer--mini-variant` selector. They do not assert a shared bottom-item class, equal prepend/title geometry, flag use of the same prepend slot, removal of Language-only offsets, or safe-area padding.
- Planned fix: add one shared `navigation-bottom-item` class to all expanded controls, consolidate their prepend/content/title/flag geometry into one CSS contract matching main navigation, remove Language-only offsets, and apply one safe-area-aware append/mini-stack spacing rule. Collapsed controls retain their existing button behavior and geometry.

REG-009 implementation and verification:

- Changed `resources/js/components/Layout.vue`: Hide, Theme, and Language now share `navigation-button navigation-bottom-item`; Language retains only its semantic `navigation-language-button` marker. All three labels use the same plain Vuetify 3 title slot, and the flag remains in the same `#prepend` slot as the icons.
- Changed `resources/sass/app.scss`: one bottom-item contract now owns 40px row height, a stable 40px prepend column, flex-start icon/flag alignment, zero title padding, and centered 20px title line height. The Language-only positive/negative margins and the separate bottom-title rule were removed. The append owns `12px + env(safe-area-inset-bottom)` spacing; the mini stack no longer adds a competing fixed bottom inset.
- Changed `tests/Feature/VueMigrationStaticTest.php`: `test_sidebar_bottom_controls_share_one_vuetify_3_alignment_contract` requires all three controls to use the shared class and Vuetify 3 prepend slot, verifies the shared geometry and safe-area rule, and rejects the removed Language-only offsets and title class. The test failed before the production change and passed afterward.
- Desktop browser verification at 1440x1000: expanded rows were all 40px high; every icon/flag began at x=16 and every label at x=56; icon/flag-to-label vertical center delta was 0px. The collapsed stack retained three centered 46x46 buttons and 12px bottom spacing.
- Mobile browser verification at 390x844: the temporary drawer remained 256px wide and ended above the fixed bottom navigation; all rows were 40px high with x=16 visual and x=56 label alignment, 0px center delta, and 28px between the last row and viewport bottom. The drawer itself introduced no horizontal overflow. A pre-existing 2px Home goal-card overflow relative to the scrollbar-reduced document client width was observed and left unchanged because it is outside REG-009.
- Light and dark verification: light labels/icons computed as `rgb(51, 51, 51)` on white; dark labels computed as `rgb(236, 236, 241)` and icons as `rgb(226, 225, 232)` on `rgb(40, 39, 44)`. Keyboard focus plus Enter on Theme opened the theme dialog; navigation and switching behavior were unchanged.
- Automated verification: focused test `1 test, 10 assertions`; full `VueMigrationStaticTest` `13 tests, 116 assertions`; full PHPUnit suite `71 tests, 912 assertions`; `npm run check:migration`; production build; `npm run check:css` with zero errors and existing warnings only; and `git diff --check`.
- Remaining risk: `env(safe-area-inset-bottom)` was verified structurally and with the 12px fallback in Chromium. Hardware iOS inset injection was not available in the isolated runtime.

## REG-010: Vocabulary import user manual link

Status: Verified
Area: Documentation / Routing
Observed current behavior: Vocabulary import modal `user manual` link opened an empty or incorrect page.
Old behavior: Old import dialog linked to `/user-manual/vocabulary-import`, and old app registered a `UserManualVocabularyImport` Vue component; old routes accepted `/user-manual/{currentPage?}`. See old `resources/js/components/Vocabulary/VocabularyImportDialog.vue:1-27`, old `resources/js/app.js:202-214`, old `routes/web.php:101-122`.
Current implementation: Current manual route still accepts `/user-manual/{currentPage?}`, but current link targets the existing markdown manual page and anchor: `/user-manual/Setup#Importing%20Vocabulary%20into%20LinguaCafe`. See current `resources/js/components/Vocabulary/VocabularyImportDialog.vue:1-27`, current `routes/web.php:97-118`, and `manual/Setup.md`.
Difference classification: Stale route after manual-content migration
Evidence: The old dedicated `UserManualVocabularyImport` component remains in the source tree, but the current `UserManual.vue` renders Markdown files selected by the `currentPage` route parameter. The old `/user-manual/vocabulary-import` target therefore requested a nonexistent `manual/vocabulary-import.md`. The current Setup target exists, renders the heading ID `importing-vocabulary-into-linguacafe`, and contains the CSV import instructions.
Recommended action: Keep current Markdown target and protect its complete route/content/anchor contract
Risk: Low
Tests needed: Component, Static route/manual regression
Fix status: Applied before this task; browser and static verified on 2026-06-19
Notes:

- No production-code change was required in this task because the current modal target was already correct.
- Strengthened `tests/Feature/VueMigrationStaticTest.php::test_vocabulary_import_manual_link_targets_existing_markdown_section` to require the exact current href, the Laravel manual route, the target heading and CSV instructions, the manual renderer's heading-ID and page-name normalization contracts, and absence of the old stale href.
- Isolated browser verification at `http://127.0.0.1:8082`: opening Vocabulary > Data > Import and clicking `user manual` navigated to `/user-manual/Setup#Importing%20Vocabulary%20into%20LinguaCafe`. The rendered manual contained 22,083 text characters, the expected heading, and the CSV column instructions; the heading was positioned approximately 39px below the viewport top after navigation.
- Direct refresh of the same URL returned the same non-empty content and positioned the target heading approximately 1px from the viewport top. No browser console route or navigation errors were reported.
- The link preserves the old same-tab behavior. A raw internal anchor is acceptable here because the Laravel fallback route serves the Vue app on direct navigation, and the current manual component resolves both the route parameter and hash after loading Markdown.
- Automated verification: focused REG-010 test `1 test, 7 assertions`; full PHPUnit suite `71 tests, 917 assertions`; `npm run check:migration` including the production build; and `git diff --check`.

## REG-011: Home mobile goal-card horizontal overflow

Status: Verified
Area: Home dashboard / Responsive layout
Observed current behavior: At mobile widths, fixed goal-card sizing can make the Home document wider than its scrollbar-reduced client width.
Current implementation: `resources/sass/Home/Home.scss` first gives `#goals` a single responsive grid track below 700px, but later mobile rules at 575px and 355px override each `.goal` to `width: 360px`, `max-width: 360px`, and 8px left/right margins.
Difference classification: New unrelated responsive CSS regression
Evidence:

- Isolated browser at a 390x844 viewport: `document.documentElement.clientWidth` was 382px and `scrollWidth` was 384px. `#home` was 382px wide with 16px side padding, leaving `#goals` 350px wide. Each card was 360px wide with 8px left/right margins, positioned from x=24 to x=384.
- At 320x844, client width was 312px and scroll width remained 384px, producing 72px overflow because the same 360px card and margins remained active inside a 280px goals column.
- At 900x900 and 1440x1000, client width equaled scroll width. Cards were 360px wide with no mobile margins, so the defect is limited to the later mobile overrides rather than the base flex/grid layout.
- The card itself uses `box-sizing: border-box`; its 1px outlined borders are included in the 360px computed width. The overflow is caused by the fixed width plus margins, not border-box arithmetic, grid gap, or the bottom navigation.

Recommended action: Keep the 360px visual maximum, but allow each mobile card to shrink to its single grid track with `width: 100%`, center it using grid alignment/automatic margins, and remove fixed 8px side margins. Do not mask the issue with global overflow clipping.
Risk: Low
Tests needed: Static responsive-contract test and browser geometry verification
Fix status: Applied and verified on 2026-06-19
Notes:

- Changed `resources/sass/Home/Home.scss`: the <=575px goal card keeps a 360px visual maximum but now uses `width: 100%`, `min-width: 0`, `justify-self: center`, and automatic inline margins. Removed the duplicate <=355px fixed 360px goal-card override. No global overflow rule was added.
- Changed `tests/Feature/VueMigrationStaticTest.php`: `test_home_mobile_goal_cards_shrink_to_the_available_grid_track` requires the responsive goal-card contract and rejects reintroduction of fixed 360px goal widths or 8px goal-card side margins.
- Post-fix browser verification:
  - 390x844 light: client width 382px, scroll width 382px; `#goals` and each card were 350px wide from x=16 to x=366. Card title remained 64px high, body content 348px wide, and actions 53px high.
  - 320x844 light: client width and scroll width were both 312px; `#goals` and cards shrank to 280px. Goal descriptions and action rows had no internal horizontal overflow.
  - 900x900 and 1440x1000: client width equaled scroll width; cards retained their existing 360px desktop/tablet maximum and 400px height.
  - 390x844 dark: client width and scroll width were both 382px; cards remained 350px wide. Card, description, and action text retained the verified dark semantic foreground.
- Automated verification: focused responsive test `1 test, 3 assertions`; full PHPUnit suite `72 tests, 920 assertions`; `npm run check:migration` including the production build; `npm run check:css` with zero errors and existing warnings only; and `git diff --check`.
- Bottom navigation and sidebar rules were untouched. Scope is limited to Home goal cards; similar fixed-width statistics/about rules were not implicated in the measured overflow and were not changed.

## Fixed / Already Reconciled

- REG-001 invalid vocabulary tokens: fixed by central classifier, valid-token query scope, CSV import validation, and cleanup command.
- REG-002 hyphenated words/phrases: fixed by internal-hyphen validation and tokenizer token coalescing.
- REG-003 Vocabulary book/chapter filter: fixed by id-based filter with migrated-data fallback.
- REG-004 chapter pagination/statistics: fixed by server pagination metadata, all-mode, per-page validation, and visible/all count calculation.
- REG-005 shared table action buttons: fixed by `.table-action-button` and compact action columns.
- REG-006 Reader/Review toolbar circles: fixed by `.vertical-toolbar-button` and Reader toolbar rail sizing.
- REG-007 dialogs/edit chips: fixed by `.app-dialog-card`, current dialog model bindings, and Vuetify 3 chip sizing/icons.
- REG-009 sidebar bottom controls: fixed with one Vuetify 3 prepend/title geometry contract and verified on desktop, collapsed rail, and mobile drawer in light and dark themes.
- REG-010 manual link: fixed by linking to existing Setup markdown anchor.

## Deferred / Decision Needed

- REG-008 dark theme: source reconciliation shows the migration direction, but broad contrast issues need a focused light/dark visual audit before additional CSS edits.
- REG-003 frontend stale-state E2E: backend is tested; a browser/component test should still cover selecting Book A, Book B, and Any in the UI.
- REG-004 footer overlay visual positioning: static tests cover custom positioning code; browser visual verification should remain part of release validation.
- REG-009 mobile drawer cramped state: requires visual verification across mobile viewport sizes.

## Tests Added Or Updated During This Reconciliation

- `tests/Feature/ChapterTest.php`: updated chapter-statistics fixtures to use valid letter-only vocabulary tokens after the central valid-token scope correctly rejected underscore/digit fixture words. This keeps REG-004 tests aligned with REG-001 token rules.
- `tests/Feature/TextBlockServiceTest.php`: structured reason codes, all reported invalid tokens, valid composed/decomposed Unicode, Cyrillic, CJK, contractions, internal apostrophes/hyphens, tokenizer persistence, and SQL/PHP classifier parity.
- `tests/Feature/VocabularyTest.php`: invalid spelling update returns `422`, legacy invalid rows are filtered from search, CSV rejects invalid tokens, and valid hyphenated words remain importable.
- `tests/Feature/CleanupNonWordVocabularyTest.php`: dry-run reporting, user scoping, safe delete, quarantine, ambiguity skip, association reporting, metadata/counter repair, idempotency, and daily review-goal filtering.
- `tests/Feature/BackfillVocabularyMetadataTest.php`: dry-run/apply word and phrase ID repair, preservation of current IDs, idempotency, and duplicate-text ambiguity without mutation.
- `tests/Feature/MigrationSmokeTest.php`: valid lexical word editing and Review filtering of invalid legacy rows.

## Tests Added Or Updated Before This Reconciliation

- `tests/Feature/TextBlockServiceTest.php`: invalid tokens, valid hyphenated/Unicode words, hyphenated reader words, phrase selection.
- `tests/Feature/VocabularyTest.php`: duplicate word text by book id, missing/stale chapter ids fallback, existing invalid token filtering, CSV import rejection.
- `tests/Feature/CleanupNonWordVocabularyTest.php`: dry-run/apply behavior, review cleanup, chapter/book metadata repair.
- `tests/Feature/ChapterTest.php`: pagination validation, totals, all-mode, large book statistics.
- `tests/Feature/VueMigrationStaticTest.php`: table action classes, toolbar classes, dialog sizing guards, footer select positioning, manual link target.

## Verification Log

Source evidence was collected from the old and current files listed above. The 2026-06-18 follow-up applied the remaining REG-003 backend and frontend correctness fixes and strengthened REG-004 verification without changing its production backend implementation.

Targeted verification run after tracker update:

Follow-up verification on 2026-06-18:

- Red state: mixed chapter metadata returned 1 word instead of 2; missing phrase metadata returned 0 phrases instead of 1; Vocabulary static contract lacked a request sequence.
- Green state: focused suite passed 30 tests and 523 assertions.
- `npm run check:migration` passed, including production Vite build.
- `node --check scripts/check-legacy.js` passed.
- `npm run check:css` passed with 0 errors and 359 existing warnings.
- Browser: Silo 85 results, Candela Obscure 2 results; chapter totals remained 83 for 10, 25, 50, page 2, and All.

Original reconciliation verification:

```bash
node --check scripts/check-legacy.js # passed
npm run check:migration # passed, including production Vite build
npm run check:css # passed critical check; existing warning-only CSS debt remains
git diff --check # passed
docker run ... vendor/bin/phpunit tests/Feature/TextBlockServiceTest.php tests/Feature/VocabularyTest.php tests/Feature/CleanupNonWordVocabularyTest.php tests/Feature/ChapterTest.php tests/Feature/VueMigrationStaticTest.php # passed: 37 tests, 231 assertions
```

A first PHPUnit run failed because `ChapterTest` used invalid underscore/digit fixture tokens such as `word_51`, which are correctly filtered by the current valid-token scope. The fixture was updated to valid letter-only tokens and the suite passed on rerun.
