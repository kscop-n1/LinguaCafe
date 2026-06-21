# Migration Regression Stabilization Checkpoint

Date: 2026-06-20

Source of record: `release-notes/regression-reconciliation-tracker-2026-06-17.md`

This checkpoint records the stabilized REG-001 through REG-011 work and the four completed REG-008 dark-theme batches. It does not declare the migration or dark-theme audit complete.

## Completed Clusters

### REG-001: Vocabulary token integrity and legacy metadata safety

- Status: Verified.
- Root cause: old and migrated pipelines accepted processed tokens without one shared lexical contract; SQL filtering, cleanup, review goals, and metadata repair could diverge.
- Changed files: `app/Services/TextBlockService.php`, `app/Services/VocabularyService.php`, `app/Http/Controllers/VocabularyController.php`, `app/Models/Goal.php`, `app/Models/EncounteredWord.php`, `app/Console/Commands/CleanupNonWordVocabulary.php`, `app/Console/Commands/BackfillVocabularyMetadata.php`, and focused feature tests.
- Tests: structured rejection reasons; Unicode, contractions, and lexical hyphens; SQL/PHP parity; import/edit rejection; search/review filtering; cleanup dry-run/apply, delete/quarantine, ambiguity and idempotency; metadata backfill dry-run/apply and duplicate ambiguity.
- Verification: isolated cleanup deleted one pristine invalid row, quarantined one reviewed row, skipped one ambiguous row, repaired chapter/book totals, and became a no-op on rerun. Backfill repaired only unambiguous phrase metadata and refused duplicate word text. No production apply was run.
- Residual risk: duplicate `encountered_words.word` rows remain intentionally ambiguous. A production cleanup/backfill review and product/data decision are required before removing the temporary REG-003 text fallback.

### REG-003 / REG-004: Vocabulary filtering and chapter pagination/statistics

- Status: Verified.
- Root cause: the performance refactor enabled ID filtering before mixed legacy metadata had complete ID coverage; the frontend lacked latest-request protection. Chapter correctness depended on a stable server-pagination contract and explicit All mode.
- Changed files across the cluster: `app/Services/VocabularyService.php`, `resources/js/components/Vocabulary/Vocabulary.vue`, `tests/Feature/VocabularyTest.php`, `tests/Feature/ChapterTest.php`, `tests/Feature/VueMigrationStaticTest.php`, `tests/frontend/Vocabulary.spec.js`, `tests/frontend/setup.js`, `vitest.config.mjs`, `package.json`, and `package-lock.json`.
- Tests: Book A/Book B/Any words and phrases; partial/missing/stale metadata; static and mounted stale success/error guards; page sizes 10/25/50; stable total 83; page 2; All via `all=true`; controlled invalid pagination; all five statistics beyond chapter 50; valid numeric zero.
- Verification: isolated browser data showed Silo 85 results and Candela Obscure 2 results. Chapters displayed 1-10, 1-25, 1-50, 51-83, and All 1-83 while total remained 83. Chapter 83 correctly displayed Total 0.
- Mounted async verification added on 2026-06-20: `tests/frontend/Vocabulary.spec.js` starts Book A and Book B requests with deferred promises, resolves Book B first, then proves a late Book A success or error cannot replace Book B state, rendered text, active filter, or completed loading state. `npm run test:frontend -- --reporter=verbose` passed `2` tests. Production behavior was unchanged.
- Residual risk: legacy text fallback remains for ambiguous or incomplete metadata.

### REG-005: Shared compact table actions

- Status: Verified.
- Root cause: migrated Vuetify 3 buttons inherited global text-button padding, and some tables bypassed the shared action contract.
- Changed files: `resources/sass/app.scss`, `resources/js/components/Library/BookChapters.vue`, `resources/sass/Library/BookChapters.scss`, `resources/js/components/Library/BookListLayout/BookListTable.vue`, and `tests/Feature/VueMigrationStaticTest.php`.
- Tests: shared class usage, fixed 32x32 geometry, padding exclusions, and 96px action columns.
- Verification: Admin Users, Dictionaries, Fonts, Vocabulary, Book List, and Book Chapters measured 32x32 with centered visible icons in light and dark themes.
- Residual risk: new table implementations must opt into the shared class; browser geometry remains the strongest validation.

### REG-006: Reader and Review vertical toolbars

- Status: Verified.
- Root cause: global button padding stretched Review controls because Reader and Review did not previously share an authoritative Vuetify 3 icon-button contract.
- Changed files: `resources/sass/app.scss`, Reader/Review component class usage, and `tests/Feature/VueMigrationStaticTest.php`.
- Tests: shared `vertical-toolbar-button` use and fixed 40x40 circular geometry.
- Verification: eight Reader and seven Review controls measured 40x40 with zero padding and 50% radius at 1440px and 900px. Reader panel/toolbar separation was 8px with no overlap.
- Residual risk: broader Reader/Review dark animation and state colors remain under REG-008.

### REG-007: Dialogs, metadata chips, and Reader Settings

- Status: Verified.
- Root cause: fixed-height dialog assumptions and Vuetify 2 card/chip/slider selectors did not match Vuetify 3 DOM. Reader Settings lacked a shared label/control row contract.
- Changed files: `resources/sass/app.scss`, `resources/js/components/Vocabulary/VocabularyEditDialog.vue`, `resources/sass/Vocabulary/VocabularyEditDialog.scss`, `resources/js/components/TextReader/TextReaderSettings.vue`, `resources/sass/TextReader/TextReader.scss`, and `tests/Feature/VueMigrationStaticTest.php`.
- Tests: viewport-aware dialog flex/overflow contract; visible metadata labels and semantic chip contrast; 23 shared settings rows; current activator/model events; slider/switch containment; mobile tab arrows; obsolete selector rejection.
- Verification: normal desktop dialogs had no unnecessary body scroll; mobile Upload Font scrolled only the body with actions visible. Word/Phrase chip labels were readable. Reader Settings passed 1440px, 900px, and 390px light/dark geometry and keyboard/update checks without horizontal overflow.
- Residual risk: none specific beyond continued visual regression coverage and the separate REG-008 theme families.

### REG-008: Dark-theme shared component batches

- Status: Four shared clusters verified; REG-008 remains open for deferred families.
- Root cause: semantic Vuetify 3 tokens existed, but obsolete selectors, teleported overlay scope, hardcoded foregrounds, and competing generic rules applied incorrect state colors.
- Changed files across completed batches: `resources/js/themes.js`, `resources/js/components/Home/Calendar.vue`, `resources/sass/DarkMode.scss`, `resources/sass/app.scss`, `resources/sass/Home/Home.scss`, `scripts/check-dark-theme-contrast.js`, and `tests/Feature/VueMigrationStaticTest.php`.
- Tests: semantic token contrast; active pagination and error actions; current field/focus/disabled/menu selectors; teleported overlay coverage; obsolete selector rejection; semantic tab/sidebar/bottom-navigation states; current Vuetify 3 date-picker selectors and semantic selected/today/hover/disabled/custom-calendar states.
- Tables/pagination/headings verification: Admin Fonts, Dictionaries, and Vocabulary. Active pagination improved to 7.08:1; delete actions to 5.82:1; table borders to 3.16:1 while retaining 32x32 actions.
- Forms/selects/cards/menus verification: Vocabulary filters/import, Edit User, Reader Settings, menus, and help popovers. Alert link improved from 1.11:1 to 7.08:1; selected menus were 9.00:1; focused fields used the semantic focus indicator.
- Tabs/navigation verification: all Admin and Reader Settings tabs, desktop sidebar, mobile drawer, and bottom navigation at 1440px, 900px, and 390px. Active sidebar and mobile navigation reached 7.08:1 with visible hover/focus/selection states and no overflow.
- Calendar/date-picker verification: Home's teleported picker, custom goal-calendar days, and day-details popup were checked at desktop and 390px mobile widths. Picker text reached 11.26:1, primary header/selected text and popup controls reached 7.08:1, semantic borders reached 3.16:1, keyboard focus was visible, and no mobile overflow occurred.
- Residual risk: Review animation/state colors, feature-local obsolete selectors, broad legacy cleanup, and the light-palette `on-primary` contrast decision remain deferred.

### REG-009: Sidebar and mobile drawer bottom controls

- Status: Verified.
- Root cause: Hide, Theme, and Language used duplicated Vuetify 3 layout rules and Language-specific offsets instead of one prepend/title geometry contract.
- Changed files: `resources/js/components/Layout.vue`, `resources/sass/app.scss`, and `tests/Feature/VueMigrationStaticTest.php`.
- Tests: shared bottom-item class, prepend/title geometry, flag placement, removed offsets, and safe-area padding.
- Verification: desktop and mobile rows measured 40px high with x=16 visual, x=56 label, and zero vertical-center delta. Collapsed controls remained centered 46x46. Light/dark keyboard behavior was unchanged.
- Residual risk: `env(safe-area-inset-bottom)` was verified structurally and in Chromium, not on physical iOS hardware.

### REG-010: Vocabulary import manual link

- Status: Verified.
- Root cause: the old dedicated manual component route became stale after documentation moved to Markdown pages.
- Changed files: no production change was needed in the focused task; `tests/Feature/VueMigrationStaticTest.php` was strengthened.
- Tests: exact current href, Laravel route, Markdown page and section text, heading normalization, and rejection of the old stale href.
- Verification: modal click and direct refresh both opened `/user-manual/Setup#Importing%20Vocabulary%20into%20LinguaCafe` with non-empty relevant CSV instructions and no console navigation errors.
- Residual risk: low; future documentation heading changes must update the protected anchor contract.

### REG-011: Home mobile goal-card overflow

- Status: Verified.
- Root cause: later mobile rules forced a 360px card plus side margins inside narrower responsive grid tracks.
- Changed files: `resources/sass/Home/Home.scss` and `tests/Feature/VueMigrationStaticTest.php`.
- Tests: responsive shrink contract and rejection of fixed mobile width/margins.
- Verification: client width equaled scroll width at 390px, 320px, 900px, and 1440px in representative light/dark checks. Cards retained their 360px desktop maximum and readable mobile content.
- Residual risk: similar fixed-width rules elsewhere were not implicated and were intentionally not changed.

## Protected Baseline

Latest known passing checkpoint:

- PHPUnit: `95 tests, 1,062 assertions`.
- Mounted frontend component tests: `1` file, `2` tests passed.
- Focused current REG-008 calendar/date-picker test: `1 test, 22 assertions`.
- `npm run check:migration`: passed, including dependency checks, hard legacy blockers, and production Vite build.
- Production build: passed with 749 modules transformed.
- `npm run check:css`: passed with zero errors; warning-only legacy CSS debt remains.
- `node scripts/check-dark-theme-contrast.js`: passed.
- `node --check scripts/check-dark-theme-contrast.js`: passed.
- `git diff --check`: passed.
- Browser scope: representative desktop 1440px, narrow/tablet 900px, mobile 390px, and targeted 320px checks; light/dark themes; Admin tables/tabs, Vocabulary, Library chapters, Reader/Review toolbars, dialogs, Reader Settings, sidebar/drawer/bottom navigation, manual routing, and Home responsive geometry.

Protected behavioral contracts include:

- structured vocabulary classification and safe dry-run maintenance commands;
- distinct book filtering with stale-response guards;
- stable server totals, explicit All mode, and complete numeric chapter statistics;
- 32x32 table actions and 40x40 toolbar controls;
- viewport-aware dialogs and readable metadata chips;
- shared responsive Reader Settings rows;
- semantic dark-theme states for the four completed component clusters;
- aligned safe-area-aware sidebar controls;
- valid import documentation routing;
- no Home goal-card document overflow.
- guarded cleanup reporting with independent reason/token selectors, exclusions and reviewed allow-tokens, book/chapter scope, mandatory user/language/count gates, and mutually exclusive delete-only/quarantine-only apply modes.

## Remaining Deferred Work

- REG-008 Review animation/state colors and broader Reader/Review contrast verification.
- REG-008 feature-local obsolete selectors, including Admin API settings.
- REG-008 broad legacy selector cleanup, performed only in evidence-backed component batches.
- Separate light-palette `on-primary` contrast decision; the current primary/white ratio is 3.23:1.
- Production cleanup/backfill apply remains blocked after the production-backup reviews. Cleanup now has tested selectors and mandatory guards, but no cleanup apply scope is approved. Broad metadata backfill remains prohibited.
- The fresh wrong-owner phrase-repair dry-run is documented in `release-notes/production-maintenance-fresh-dry-run-report-2026-06-21.md`: user `3`, language `english`, book `4`, 12 chapters, 16 unique remaps, 38 embedded occurrences, and zero unresolved/ambiguous/missing cases.
- A pending approval packet exists at `release-notes/wrong-owner-phrase-repair-apply-approval-2026-06-21.md`. It documents the exact future command, all 16 candidates, pre/post checks, and rollback plan. It does not authorize execution; a fresh matching backup dry-run and separate explicit human approval are still mandatory.
- Physical iOS safe-area verification for sidebar/mobile drawer bottom controls.
- Continued release-time browser verification for chapter footer overlay positioning.

## Risk Register

| Risk | Severity | Why deferred | Recommended next action | Work type |
| --- | --- | --- | --- | --- |
| Production contains invalid or incomplete legacy vocabulary metadata | High | The production-backup dry run found 2,398 invalid rows, but 449 mechanically safe candidates include debatable lexical forms and 440 require manual review | Repeat the report against a fresh backup using the implemented narrow selectors and ceiling, then approve an exact candidate list before any apply | Product decision, dry-run, manual verification |
| Wrong-owner phrase metadata requires controlled production repair | High | A fresh backup dry-run produced one clean scope, but no production apply has been approved or executed | Follow the pending approval packet: make a new backup, repeat the scoped dry-run, require an exact 16-row match, and obtain explicit approval before apply | Approval decision, backup, dry-run, manual verification |
| Duplicate word text prevents deterministic metadata backfill | Medium | The reviewed snapshot has no duplicates within user/language scope, but future datasets may; automatic guessing remains unsafe | Keep duplicate detection and fallback; define a policy only if a future dry run reports scoped duplicates | Product decision, tests |
| Review animation/state colors are not comprehensively audited | Medium | Toolbar geometry was protected; animation surfaces need separate runtime states | Capture each Review transition/state in light/dark and fix only proven shared rules | Browser verification, code, tests |
| Admin API and other feature-local obsolete selectors may be dead or harmful | Medium | Blind removal could alter unverified pages | Audit one feature at a time and replace only selectors with runtime impact | Code, tests, browser verification |
| Broad legacy CSS debt remains | Medium | `check:css` warnings are numerous and not all represent defects | Convert warnings into small ownership-based batches, retaining the zero-error gate | Code, tests |
| Light `on-primary` contrast is 3.23:1 | Medium | Changing the palette affects many components and needs design/product agreement | Decide whether primary, foreground, or usage should change; then run full light-theme visual checks | Product decision, code, visual verification |
| iOS hardware safe-area behavior is unverified | Low | Chromium can validate structure but not real device inset behavior | Verify drawer and bottom controls on a physical iPhone/Safari or trusted device farm | Manual verification |
| Chapter footer overlay placement could regress with Vuetify changes | Low | Static location strategy is covered, but overlay placement is browser-dependent | Keep a targeted Library browser smoke check in release validation | Manual verification, optional E2E |

## Recommended Next Execution Order

1. Review and approve or reject the pending wrong-owner phrase-repair packet; execution remains a separate task with a new backup and matching final dry-run.
2. Keep cleanup and broad metadata backfill apply blocked; prepare a separate exact-token cleanup packet only if product policy approves specific candidates.
3. Audit Review animation/state colors and broader Reader/Review contrast without changing established toolbar geometry.
4. Continue feature-local obsolete-selector cleanup in evidence-backed batches.
5. Address the light-palette `on-primary` decision only after product/design approval.

## Working Tree Report

Current state after the calendar/date-picker dark-theme batch:

```text
git status --short
 M release-notes/regression-reconciliation-tracker-2026-06-17.md
 M release-notes/regression-stabilization-summary-2026-06-20.md
 M resources/js/components/Home/Calendar.vue
 M resources/sass/DarkMode.scss
 M resources/sass/Home/Home.scss
 M resources/sass/app.scss
 M scripts/check-dark-theme-contrast.js
 M tests/Feature/VueMigrationStaticTest.php
```

- `package-lock.json`: unchanged.
- Generated/runtime artifacts: none remain; the production build did not leave tracked build changes.
- Test data: one disposable audit user was created only in the development database and removed after browser verification.
- Production data: no cleanup, backfill, or phrase-repair command was run.
- Verification: focused calendar/date-picker `1 test, 22 assertions`; full PHPUnit `95 tests, 1,062 assertions`; mounted frontend `2 tests`; migration/production build, CSS audit, contrast guard, and `git diff --check` passed.
