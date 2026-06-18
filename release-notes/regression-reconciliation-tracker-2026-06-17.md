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
- Remaining risks: the stale-response guard has a static source-contract test and manual browser verification, but no mounted Vue component test that resolves two mocked requests out of order. Text fallback for partially migrated word metadata preserves old compatibility behavior and can still be ambiguous when duplicate `encountered_words.word` rows exist; complete ID backfill remains the durable resolution.

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

Status: Classified
Area: Theme
Observed current behavior: Dark-on-dark text/icons appear in several places, including fonts/admin pages and toolbars/action icons.
Old behavior: Old global CSS targeted Vuetify 2 theme classes and variables such as `.theme--light`, `.v-menu__content`, `var(--v-foreground-base)`, `var(--v-text-base)`, and active list item icon colors. See old `resources/sass/app.scss:123-152`, `:171-205`.
Current implementation: Current theme tokens live in `resources/js/themes.js` and migrated CSS uses `rgb(var(--v-theme-*));`, with broad overlay/list/form overrides in `resources/sass/app.scss`. Current dark palette defines explicit `text`, `textSecondary`, `icon`, `foreground`, `background`, and `primary` colors in `resources/js/themes.js:41-70`.
Difference classification: Incorrect Vuetify 2 -> Vuetify 3 migration
Evidence: Search still finds hardcoded colors and many theme token overrides in current `resources/sass/app.scss`; some are intentional white-on-primary or light input fixes, so this needs contrast-focused review rather than blind replacement.
Recommended action: Create separate performance/product decision
Risk: Medium
Tests needed: Component, Visual regression, Static/CSS contrast audit
Fix status: Deferred
Notes: The broad dark-theme category is not fully closed by old/current source comparison. Need browser screenshots/light-dark contrast checks for concrete screens before editing.

## REG-009: Sidebar/mobile bottom icon alignment

Status: Verified
Area: Sidebar / Mobile drawer
Observed current behavior: Hide, Theme, and Language bottom-control icons did not align with labels; mobile bottom area could be cramped.
Old behavior: Old large drawer bottom controls used inline `<v-icon>`/`<v-img>` followed by manual padding spans. Mini drawer used standalone rounded text buttons. See old `resources/js/components/Layout.vue:44-76`.
Current implementation: Current large drawer uses Vuetify 3 `#prepend` slots and `v-list-item-title` for consistent icon/title layout; mini drawer wraps controls and uses `navigation-flag` classes. See current `resources/js/components/Layout.vue:50-88`; supporting CSS is in `resources/sass/app.scss` bottom-navigation rules.
Difference classification: Incorrect Vuetify 2 -> Vuetify 3 migration
Evidence: Current markup uses the same structural pattern as main list items rather than manual span padding. Static UI regression coverage indirectly requires current shared UI patterns in `tests/Feature/VueMigrationStaticTest.php:188-208`; visual/mobile verification remains needed for cramped drawer state.
Recommended action: Port old approach correctly to current framework
Risk: Medium
Tests needed: Component, Visual regression, Static regression
Fix status: Already applied before this reconciliation; visual verification still recommended
Notes: Do not restore manual padding spans; use Vuetify 3 prepend/title layout.

## REG-010: Vocabulary import user manual link

Status: Verified
Area: Documentation / Routing
Observed current behavior: Vocabulary import modal `user manual` link opened an empty or incorrect page.
Old behavior: Old import dialog linked to `/user-manual/vocabulary-import`, and old app registered a `UserManualVocabularyImport` Vue component; old routes accepted `/user-manual/{currentPage?}`. See old `resources/js/components/Vocabulary/VocabularyImportDialog.vue:1-27`, old `resources/js/app.js:202-214`, old `routes/web.php:101-122`.
Current implementation: Current manual route still accepts `/user-manual/{currentPage?}`, but current link targets the existing markdown manual page and anchor: `/user-manual/Setup#Importing%20Vocabulary%20into%20LinguaCafe`. See current `resources/js/components/Vocabulary/VocabularyImportDialog.vue:1-27`, current `routes/web.php:97-118`, and `manual/Setup.md`.
Difference classification: Incorrect Vuetify 2 -> Vuetify 3 migration
Evidence: Static test verifies both the link and the target markdown heading in `tests/Feature/VueMigrationStaticTest.php:210-217`.
Recommended action: Port old approach correctly to current framework
Risk: Low
Tests needed: Component, Static route/manual regression
Fix status: Already applied before this reconciliation; static verified
Notes: This is a route/content migration, not a UI styling issue.

## Fixed / Already Reconciled

- REG-001 invalid vocabulary tokens: fixed by central classifier, valid-token query scope, CSV import validation, and cleanup command.
- REG-002 hyphenated words/phrases: fixed by internal-hyphen validation and tokenizer token coalescing.
- REG-003 Vocabulary book/chapter filter: fixed by id-based filter with migrated-data fallback.
- REG-004 chapter pagination/statistics: fixed by server pagination metadata, all-mode, per-page validation, and visible/all count calculation.
- REG-005 shared table action buttons: fixed by `.table-action-button` and compact action columns.
- REG-006 Reader/Review toolbar circles: fixed by `.vertical-toolbar-button` and Reader toolbar rail sizing.
- REG-007 dialogs/edit chips: fixed by `.app-dialog-card`, current dialog model bindings, and Vuetify 3 chip sizing/icons.
- REG-009 sidebar bottom controls: fixed structurally with Vuetify 3 prepend/title layout; needs visual pass on mobile drawer.
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
