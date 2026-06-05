# Regression Root Cause and Fix Plan - 2026-06-05

Source backlog: `release-notes/regression-analysis-backlog-2026-06-05.md`.

Current fork checkout: `/data/git/LinguaCafe`.
Old/original reference checkout: `/data/git/old_LinguaCafe/LinguaCafe`.
Production stack: `/home/serhii/docker/linguacafe` is production only; do not hotfix or build release images there.

This document separates confirmed findings from assumptions. Static comparison was done against the old checkout where useful. Firefox/mobile-only symptoms still need browser reproduction before implementation sign-off.

## Summary

Confirmed migration/root-cause clusters:

1. Language side menu and modal are one cluster: the dialog still watches the Vue 2 `value` prop while Vue 3 `v-model` passes `modelValue`, and the side menu flag is not rendered through Vuetify 3's prepend slot.
2. Admin table/icon issues are part of an incomplete Vuetify 3 table/list migration: several admin `v-data-table` headers still use Vuetify 2 `text/value`, and flag images have no shared fixed-size wrapper.
3. Vocabulary book filtering is backend/data-model sensitive after the unique-word-id change: current code filters by `chapters.unique_word_ids` when available, with a fallback to word text. This is correct for duplicate word text, but can fail for old/stale chapters if `unique_word_ids` is empty, missing, or not backfilled correctly.
4. Non-word vocabulary tokens are not a clear migration regression. Current and old `TextBlockService` behavior is materially the same: it only ignores exact `words_to_skip` tokens and `is_numeric()` values, so dice/math tokens like `+1d20` become vocabulary words.
5. Mobile Firefox layout/API issues are likely responsive/migration residue, but need Firefox reproduction before root cause can be called final.

## Implementation Pass - 2026-06-05

Status: completed for confirmed Vuetify migration leftovers in this pass; remaining backend/runtime issues are still separate root-cause tasks.

Completed changes:

- Converted sidebar `v-list-item` icons and the Language flag to Vuetify 3 `#prepend` slots in `resources/js/components/Layout.vue`.
- Added a safe `selectedLanguageFlagSource` computed value for sidebar flag rendering.
- Updated the global `dialogValue` helper in `resources/js/app.js` so declared `modelValue` props work with existing dialog templates.
- Replaced dialog `value: Boolean` props with `modelValue: Boolean` across dialog components that use `:model-value="dialogValue"`.
- Moved known dialog open watchers from `value` to `modelValue`: Language selection, Start review, Admin uninstall languages, and TextReader chapter list.
- Converted confirmed Vuetify 3 data-table headers from `text/value` to `title/key` in Admin Dictionary/User/Font tables, Book chapters, TextReader chapter list, and finished-reader leveled-up words table.
- Migrated progress components from `:value` to `:model-value` where they are actual `v-progress-*` controls and added guarded computed percentages for Review and Admin API usage.
- Migrated Vocabulary pagination from legacy `@input` to `@update:model-value` and made `total-visible` responsive for narrow screens.
- Migrated remaining confirmed Vuetify model events from `@change`/`@change:model-value` to `@update:model-value` in Book chapter display toggle, Home calendar date picker, and User Settings text sample language toggle.
- Converted Vocabulary sort-menu `v-list-item` icons to Vuetify 3 `prepend-icon` usage.
- Extended `scripts/check-legacy.js` to catch the same migration residue classes: `v-pagination @input`, legacy `v-data-table` header keys, dialog `value` props/watchers with `dialogValue`, model events on `v-btn-toggle`/`v-date-picker`, and navigation list items with direct icon/image content instead of `#prepend`.

Validation completed:

- `node --check scripts/check-legacy.js`: passed.
- `npm run check:migration`: passed.
- `npm run check:css`: passed with existing warning-only CSS override debt; no critical CSS errors.
- `git diff --check`: passed.
- `npm run production`: passed.
- `npm run test:php`: passed, 24 tests and 80 assertions.
- Separate lint/typecheck scripts are not defined in `package.json`; the available repo gates above were run instead.

Remaining items after this pass:

- Vocabulary book filter backend/data-model behavior after the unique-word-id change still needs a separate root-cause pass.
- Non-word token classification still needs a parser/service fix and migration/backfill decision.
- Admin backup 401 needs runtime auth/route investigation.
- Mobile Firefox-specific API page and vocabulary overflow still need browser reproduction after these migration fixes.
- User Settings Theme UI still needs a focused layout pass beyond the migration API cleanup done here.

## Issue 1 - Side Menu Language Icon Missing

Status: confirmed migration/UI issue by static comparison; needs final browser visual verification.

Symptoms:

- The Language side-menu item does not show the selected-language flag icon after migration.

Affected files/components:

- `resources/js/components/Layout.vue`
- `resources/sass/app.scss`
- Shared flag rendering also affects Admin language/dictionary screens.

Evidence:

- Current `Layout.vue` renders the flag as direct default content inside `<v-list-item>`:
  `<v-img class="border" :src="'/images/flags/' + selectedLanguage.toLowerCase() + '.png'" ...>`.
- Vuetify 3 list items should use named slots such as `#prepend` for leading visuals. The current CSS targets `.v-list-item__prepend`, but the flag is not actually placed in that slot.
- Old code used Vuetify 2 list-item layout, where direct child placement behaved differently.

Root cause:

- Incomplete Vuetify 2 to Vuetify 3 migration for `v-list-item` leading content. CSS was adjusted for `.navigation-language-button`, but the markup still does not use Vuetify 3's prepend slot.

Proposed fix:

- Convert navigation items to explicit Vuetify 3 slots:
  - normal nav item icon: `<template #prepend><v-icon>...</v-icon></template>`
  - language item flag: `<template #prepend><img or v-img class="language-flag navigation-flag" ... /></template>`
- Add a shared `.language-flag` utility with fixed width, height, object-fit, border, and flex behavior.
- Keep sidebar spacing rules, but attach them to slot content rather than arbitrary child images.

Implementation complexity: small.

Risks:

- Sidebar compact/rail mode and bottom drawer alignment can regress if only the large drawer is updated.
- Need verify light/dark/eink and desktop/mobile drawer states.

Dependencies:

- Same shared flag utility should be reused for Issue 3.

## Issue 2 - Side Menu > Language Opens Empty Modal

Status: confirmed root cause by static comparison.

Symptoms:

- Clicking Side Menu > Language opens a modal, but it contains no language buttons.

Affected files/components:

- `resources/js/components/Dialogs/LanguageSelectionDialog.vue`
- `resources/js/app.js` global mixin for `dialogValue`/`updateValue`
- `resources/js/components/Layout.vue`

Evidence:

- Current `LanguageSelectionDialog.vue` template uses `:model-value="dialogValue"` and `@update:model-value="updateValue"`.
- `dialogValue` and `updateValue` are provided by a global mixin in `resources/js/app.js`.
- The component still declares `props: { value: Boolean }` and watches `value`:
  `watch: { value(newVal) { if (newVal) this.loadLanguages(); } }`.
- In Vue 3, parent `<language-selection-dialog v-model="languageSelectionDialog">` passes `modelValue`, not `value`. Therefore the watcher never fires and `loadLanguages()` is not called.
- Old checkout used `v-model="value"`, emitted `input`, and watched the actual prop, so the data load fired.

Root cause:

- Vue 2 `value` watcher was not migrated to Vue 3 `modelValue`/`dialogValue`. The modal can open through the global mixin, but its data-loading side effect still watches the obsolete prop.

Proposed fix:

- Replace `props: { value: Boolean }` with `props: { modelValue: Boolean }` or keep using the global `dialogValue` but watch `dialogValue`.
- Prefer local explicit model handling over relying on the global mixin for this component:
  - `props: { modelValue: Boolean }`
  - `emits: ['update:modelValue']`
  - computed `dialogValue` getter if needed
  - watcher on `modelValue` with `immediate: false`
- Add a regression test/static check for Vue components that use `v-dialog :model-value="dialogValue"` but still watch `value`.

Implementation complexity: small.

Risks:

- Similar latent issues may exist in other dialogs if they use `dialogValue` but still watch `value`.
- Need verify language modal loads installed languages and manage-language admin alert.

Dependencies:

- Issue 1 can be fixed in the same pass.

## Issue 3 - Admin Settings Language/Dictionaries Icons Look Bad

Status: partially confirmed migration issue; exact visual severity needs browser verification.

Symptoms:

- Language icons in Admin Settings > Languages and Dictionaries render inconsistently/ugly.

Affected files/components:

- `resources/js/components/Admin/AdminLanguageSettings.vue`
- `resources/js/components/Admin/AdminDictionarySettings.vue`
- `resources/sass/Admin/AdminLanguageSettings.scss`
- `resources/sass/Admin/AdminDictionarySettings.scss`
- Shared flag utility proposed for Issue 1.

Evidence:

- `AdminLanguageSettings.vue` migrated `v-simple-table` to `v-table`, but flag cells still rely on raw `<v-img class="border my-2" max-width="43" height="28">`.
- `AdminDictionarySettings.vue` uses `v-data-table`, but its `dictionaryTableHeaders` still use Vuetify 2 header keys: `text` and `value`. Vuetify 3 expects `title` and `key`.
- Custom slots are still named by old field names. Even if some slots render, the table/header model is not fully migrated.
- Old checkout used the same raw image dimensions, but under Vuetify 2 table rendering.

Root cause:

- Incomplete Vuetify 3 table/data-table migration plus lack of a shared fixed-size flag/icon wrapper.

Proposed fix:

- Convert admin data table headers from `{ text, value }` to `{ title, key }` across Admin tables.
- Verify slot names against Vuetify 3 keys.
- Replace raw repeated `v-img` flag rendering with a shared class/component, for example `.language-flag` or `<language-flag :language="..." />`.
- Add table-cell alignment rules only at component boundaries, not global image hacks.

Implementation complexity: medium.

Risks:

- Admin User and Font tables also show legacy `text/value`; fixing only Dictionaries may leave adjacent admin tabs inconsistent.
- Data-table changes can alter sorting/search behavior.

Dependencies:

- Shares flag utility with Issue 1.
- Should be covered by an extended static migration check for Vuetify 3 data-table headers.

## Issue 4 - Vocabulary Mobile Firefox Horizontal Overflow During Page Navigation

Status: reported and plausible; root cause not fully confirmed without Firefox reproduction.

Symptoms:

- On mobile Firefox, the Vocabulary page gets a horizontal side bar/overflow while navigating between pages.

Affected files/components:

- `resources/js/components/Vocabulary/Vocabulary.vue`
- `resources/sass/Vocabulary/Vocabulary.scss`
- possibly global mobile layout in `resources/sass/app.scss`

Evidence:

- Vocabulary table has fixed column widths: word/reading/word-with-reading `220px`, stage `50px`, actions `120px`. On mobile, translation is hidden, but word-with-reading + stage + actions plus padding can still exceed narrow viewport.
- `v-pagination` still uses `@input="moveToPage(currentPage)"`. Vuetify 3 emits `update:model-value`, so page navigation handling is a confirmed migration residue.
- `:total-visible="10"` is too wide for mobile and may overflow in Firefox more visibly than Chromium.
- CSS contains `width: 100;` in `#vocabulary-search-field`, which is invalid CSS and existed in old code too. It is not necessarily the new regression, but it should be corrected while working in this area.

Root cause:

- Likely combination of fixed-width table/pagination controls and a missed Vuetify 3 pagination event migration. Firefox exposes the overflow during navigation.

Proposed fix:

- Change pagination listener to `@update:model-value="moveToPage"`.
- Make pagination `total-visible` responsive, for example 5 or fewer on mobile.
- Wrap `#vocabulary-list` in a controlled horizontal-scroll container or convert narrow view to a stacked/card row layout.
- Fix invalid `width: 100;` to `width: 100%;`.
- Add a mobile overflow smoke check: assert `document.documentElement.scrollWidth <= document.documentElement.clientWidth` after loading and changing vocabulary pages.

Implementation complexity: medium.

Risks:

- Table/card conversion can change dense desktop UX; keep desktop table intact.
- Horizontal scroll inside table is acceptable only if body/page itself does not get unintended overflow.

Dependencies:

- Needs Firefox or Firefox-like verification, not Chromium only.

## Issue 5 - Admin API Page Data Broken on Mobile Firefox

Status: reported; partially confirmed migration residue, but root cause needs Firefox/runtime verification.

Symptoms:

- On mobile Mozilla Firefox, Admin Settings > API does not show API data. Desktop is OK.

Affected files/components:

- `resources/js/components/Admin/AdminApiSettings.vue`
- `resources/sass/Admin/AdminApiSettings.scss`
- possibly `resources/js/components/Admin/AdminSettingsLayout.vue`

Evidence:

- `AdminApiSettings.vue` contains confirmed migration residue: `<v-progress-linear :value="...">` should use Vuetify 3 `model-value`/`:model-value`.
- It also has malformed markup: `<label class="font-weight-bold"">`. This exists in old checkout too, so it is not necessarily a new migration regression, but Firefox may be less forgiving in mobile layout paths.
- The API tab is inside `v-window`; tab switching was migrated from `v-tabs-items/v-tab-item` to `v-window/v-window-item`. Static code looks plausible, but mobile tab/window behavior needs runtime validation.
- Current CSS has fixed skeleton widths; not obviously fatal, but could contribute to mobile overflow.

Root cause:

- Not confirmed. Leading candidates are Vuetify 3 prop residue in API usage controls, malformed markup, or mobile `v-window`/layout behavior.

Proposed fix approach:

- Reproduce in mobile Firefox and capture:
  - console errors
  - network responses for `/settings/global/get` and `/dictionaries/deepl/get-usage`
  - whether `settings`, `characterLimitStatus`, and DOM nodes exist but are hidden.
- Fix confirmed static residue regardless:
  - `<v-progress-linear :model-value="characterUsed / characterLimit * 100">`
  - remove malformed quote in label
  - guard division by zero for progress value
- If data exists but hidden, add responsive layout rules for API cards/switches.

Implementation complexity: small to medium after reproduction.

Risks:

- DeepL usage can legitimately be hidden for default/error API key states; do not confuse valid hidden state with the reported bug.

Dependencies:

- Needs admin user/runtime verification.

## Issue 6 - Admin Dashboard Backup Creation Returns 401

Status: confirmed symptom from user log; root cause not confirmed by static old/current comparison.

Symptoms:

- `GET https://lingua.lan/backups/create` from `https://lingua.lan/admin/dashboard` returns HTTP 401.

Affected files/components:

- `routes/web.php`
- `app/Http/Controllers/BackupController.php`
- `app/Http/Middleware/AdminMiddleware.php`
- `resources/js/components/Admin/AdminDashboard.vue`
- auth/session/cors configuration in production environment

Evidence:

- Current route is inside `['auth', 'auth.session', 'web']` and nested `admin` middleware.
- Old checkout has the same route and controller behavior.
- `AdminMiddleware` returns 403 for non-admin users, not 401.
- Therefore 401 means the request likely fails auth/session before admin middleware.
- User log showed `access-control-allow-origin: http://localhost:3000` on a production `https://lingua.lan` response. Current `config/cors.php` defaults `allowed_origins` to `FRONTEND_URL` or `http://localhost:3000`. This may be harmless for same-origin, but it is suspicious production config drift.
- `AdminDashboard.vue` old/current both call `axios.get('/backups/create')`, so no obvious migration diff in the component.

Root cause:

- Not confirmed. Most likely production auth/session/config issue rather than a backup-controller regression. Possibilities:
  - user session is stale or not recognized for that XHR
  - production `FRONTEND_URL`, `SESSION_DOMAIN`, or related auth/cors config is wrong
  - browser has conflicting cookies for `lingua.lan`
  - auth middleware returns 401 for XHR when session is missing

Proposed fix approach:

- Reproduce as the same admin user in production or equivalent local prod-like stack.
- Check Laravel logs for the request and confirm whether it reaches `AdminMiddleware`.
- Verify current authenticated user and `is_admin` on the request path.
- Inspect production env values for `FRONTEND_URL`, `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS`, `APP_URL`.
- Improve UI error handling to distinguish 401/403/500.
- Consider changing backup creation to POST for semantics, but that is not the cause of the 401 by itself.

Implementation complexity: medium because it needs runtime/environment evidence.

Risks:

- Do not change auth/session config blindly; it can break login globally.
- Do not test by modifying production stack files.

Dependencies:

- Needs access to production logs/container/env inspection.

## Issue 7 - User Settings > Theme UI Is Strange

Status: confirmed broader UI/layout debt; partially mitigated in v0.5.39 but not fully resolved.

Symptoms:

- Theme editor still feels visually broken/strange after migration.

Affected files/components:

- `resources/js/components/UserSettings/ThemeSettings/UserSettingsThemes.vue`
- `resources/js/components/UserSettings/ThemeSettings/UserSettingsTextStyling.vue`
- `resources/js/components/UserSettings/UserSettingsLayout.vue`

Evidence:

- Text styling still uses many raw `.w-100` setting blocks, sliders, select controls, and checkboxes rather than a reusable settings row/group.
- v0.5.39 added scoped grid/mobile CSS for the Text section, but this only mitigates one section.
- `UserSettingsThemes.vue` color table is a raw `v-table` with fixed four-column structure and no scoped mobile table strategy.
- The current version adds an Eink theme, increasing table/theme state complexity compared with old checkout.

Root cause:

- Incomplete redesign of migrated Vuetify form/table layout. Some old desktop assumptions survived, and fixes are currently section-specific rather than component-level.

Proposed fix:

- Create a small shared settings-row pattern for Theme editor controls:
  - label/help area
  - control area
  - optional secondary control/checkbox
- Apply it to `UserSettingsTextStyling.vue` instead of raw `.w-100` blocks.
- Add a mobile table strategy for Theme color tables: horizontal scroll with contained width or stacked rows.
- Keep desktop density but make mobile one-column layout deliberate.
- Verify light/dark/eink, desktop and mobile.

Implementation complexity: medium.

Risks:

- Over-refactoring theme editor can affect live preview/update behavior.
- Needs careful visual verification because this is mostly layout quality.

Dependencies:

- Could share form-row styling with Admin API settings if done carefully, but avoid broad refactor in the first fix pass.

## Issue 8 - Vocabulary Book Filter Does Not Work After Unique-Word Change

Status: confirmed high-risk backend/data-model regression area; exact production failure mode needs DB verification.

Symptoms:

- Vocabulary book filter does not work at all after changing word uniqueness approach.

Affected files/components:

- `app/Services/VocabularyService.php`
- `app/Services/ChapterService.php`
- `app/Models/Chapter.php`
- `database/migrations/2022_09_22_110938_modify_lessons_table_4.php`
- `database/migrations/2026_05_22_000002_add_unique_phrase_ids_to_chapters.php`
- `tests/Feature/VocabularyTest.php`

Evidence:

- Frontend `Vocabulary.vue` sends `book: parseInt(this.filters.book)` and route state appears correctly wired for book selection.
- Current backend filtering collects `unique_word_ids` from filtered chapters and filters `EncounteredWord.id` by those IDs when present. This was added to distinguish duplicate word text records.
- Old backend filtered only by word text from `chapter.unique_words`.
- Current code falls back to word text only when no `filteredWordIds` are collected.
- Existing `VocabularyTest::test_book_filter_uses_chapter_word_ids_instead_of_word_text` verifies the new id-based behavior only when `unique_word_ids` are present and correct. It does not cover stale/missing/empty `unique_word_ids` in migrated data.
- `ChapterService::processChapterText()` writes `unique_word_ids` for newly processed chapters, but old/prod data may depend on migration/backfill correctness.

Root cause:

- Likely stale or incorrect `chapters.unique_word_ids`/`unique_phrase_ids` in migrated production data, combined with backend reliance on those IDs for book filtering. If IDs are empty or mismatched, filtering can return wrong/no words. If IDs are present but globally unique word records were deduped differently, results can also be wrong.

Proposed fix:

- First validate production/dev DB shape:
  - count processed chapters with null/empty `unique_word_ids`
  - compare `unique_words` count vs `unique_word_ids` count
  - spot-check selected book IDs against vocabulary API response
- Add a repair/backfill command or migration that rebuilds `unique_word_ids` from `unique_words` and current `encountered_words` for each user/language.
- Make `VocabularyService` robust:
  - if `unique_word_ids` is empty but `unique_words` exists, fallback to word text
  - optionally detect count mismatch and fallback/repair
  - keep id-based filtering when valid to preserve duplicate-word behavior
- Add tests:
  - id-based filter with duplicate word text (already exists)
  - fallback when old chapter has `unique_words` but empty `unique_word_ids`
  - chapter filter as well as book filter
  - phrase filter with `unique_phrase_ids` missing but phrase ids discoverable from processed text, if needed

Implementation complexity: high.

Risks:

- A naive fallback to word text can reintroduce the duplicate-word bug that id-based filtering fixed.
- Backfill must be per user/language and avoid cross-user word ids.
- Large libraries may make repair slow; command should chunk.

Dependencies:

- Needs DB inspection against the real affected production data or a copied fixture.

## Issue 9 - Non-word Tokens Become Vocabulary Words

Status: confirmed functional debt; not clearly a migration regression.

Symptoms:

- Tokens like `#`, `'s`, `):`, `+1`, `+10d6`, `+2/+4`, `+5d6` appear as vocabulary words.

Affected files/components:

- `app/Services/TextBlockService.php`
- `config/linguacafe.php` `words_to_skip`
- Python tokenizer service endpoint called by `TextBlockService::tokenizeRawText()`
- tests around import/chapter processing

Evidence:

- Current and old `TextBlockService` are materially the same for token processing and `createNewEncounteredWords()`.
- `words_to_skip` includes exact punctuation and `+`, but not compound dice/math tokens like `+1d20`.
- `createNewEncounteredWords()` sets stage ignored only if exact token is in `words_to_skip` or `is_numeric(token)` is true.
- `+1d20` and `+2/+4` are not numeric, so they become normal stage 2 new words.
- `collectUniqueWords()` includes all processed words, including punctuation-like tokens, so they can affect chapter unique data unless filtered later.

Root cause:

- Token validation is too permissive. The app lacks a language-aware `isVocabularyToken()` rule and relies on exact skip-list/numeric checks.

Proposed fix:

- Add a central token classifier in `TextBlockService`, for example `isVocabularyToken($word, $language)`.
- Exclude or auto-ignore tokens that have no Unicode letters/marks for space-delimited languages.
- Explicitly reject/ignore dice/math/stat tokens like `+1d20`, `+2/+4`, punctuation-only tokens, and emoticon fragments.
- Be careful with valid language cases:
  - contractions and apostrophes in English
  - CJK scripts and kana/kanji
  - hyphenated words
  - languages without spaces
- Apply classifier consistently to:
  - `getWordCount()`
  - `collectUniqueWords()` or chapter unique-word persistence
  - `createNewEncounteredWords()`
  - vocabulary CSV import, if desired
- Add tests for reported examples and valid counterexamples.

Implementation complexity: medium to high.

Risks:

- Over-filtering can break legitimate words in non-English languages.
- Existing bad vocabulary rows need optional cleanup/ignore command; classifier alone only affects future imports.

Dependencies:

- Should be fixed after book-filter/backfill planning, because both touch chapter unique-word data.

## Related/Duplicate Regressions

- Issues 1, 2, and 3 share a language/flag UI surface. Fixing them should introduce a shared flag rendering helper/class.
- Issues 3, 5, and 7 share incomplete Vuetify 3 component/layout migration in admin/settings forms and tables.
- Issues 4 and 8 both affect Vocabulary but likely have separate roots: Issue 4 is responsive/UI/event migration; Issue 8 is backend data/filtering.
- Issues 8 and 9 both affect vocabulary quality and chapter unique-word data. Token filtering changes can alter unique-word ids/counts and should be tested with book filtering.
- Issue 6 is not currently tied to migration code differences; it is likely runtime auth/session/config until logs prove otherwise.

## Recommended Execution Order

1. Fix Language modal and side-menu flag (Issues 1 and 2).
   - Small, high confidence, user-visible on every page.
   - Add static check for dialogs that still watch `value` while using Vue 3 model.

2. Validate and fix Vocabulary book filter (Issue 8).
   - Highest functional impact.
   - Start with DB inspection/backfill plan, then backend tests.

3. Fix Vocabulary mobile pagination/overflow (Issue 4).
   - Address confirmed `@input` pagination migration residue and mobile overflow together.
   - Add a browser overflow smoke check.

4. Investigate backup 401 with runtime logs/env (Issue 6).
   - Do not patch blindly until auth/session failure point is known.

5. Add token classifier and tests for non-word tokens (Issue 9).
   - Requires careful language-aware rules and possible cleanup command.

6. Fix Admin language/dictionary icons and data-table migration residue (Issue 3).
   - Can share flag utility from step 1.
   - Extend static checks for Vuetify 3 data-table headers.

7. Investigate/fix Admin API mobile Firefox page (Issue 5).
   - Start with confirmed static residue, then browser/network validation.

8. Refactor User Settings Theme UI layout (Issue 7).
   - Broader visual polish; do after the higher-impact functional issues unless user prioritizes it.

## Static Check Follow-ups

Add or extend checks for:

- Dialogs/components using `dialogValue` with watchers on obsolete `value`.
- Vuetify 3 `v-data-table` headers using `text/value` instead of `title/key`.
- `v-pagination @input` and other non-form Vuetify components that require `@update:model-value`.
- Legacy direct children in `v-list-item` where `#prepend`/`#append` is required for stable icon layout.

## Browser/Runtime Verification Needed Before Fix Sign-off

- Firefox mobile Vocabulary page:
  - after initial load
  - after selecting a book filter
  - after changing pagination
  - assert no body/document horizontal overflow
- Firefox mobile Admin API tab:
  - confirm network data loads
  - confirm whether data is absent, hidden, or request fails
- Production/admin backup:
  - capture Laravel logs for `/backups/create`
  - confirm authenticated user/admin state for request
  - inspect env config related to frontend/session/cors
- Language modal:
  - verify language list loads after opening modal
  - verify selected-language flag renders in expanded and rail sidebar modes
