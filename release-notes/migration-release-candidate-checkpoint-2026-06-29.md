# Migration Release-Candidate Checkpoint - 2026-06-29

Source of record:

- `release-notes/regression-reconciliation-tracker-2026-06-17.md`
- `release-notes/regression-stabilization-summary-2026-06-20.md`

This checkpoint records the current migration stabilization state after REG-001 through REG-011 and the completed REG-008 dark-theme batches: tables/pagination/headings; forms/selects/cards/menus/overlays; tabs/navigation active states; calendar/date-picker; and Reader/Review states.

## 1. Release-Candidate Status

From a code and test perspective, the app is ready for a migration release-candidate build.

- UI migration stabilization: ready for release candidate. The protected migration regressions REG-001 through REG-011 are verified, and the completed REG-008 dark-theme clusters have focused static and browser evidence.
- Data-maintenance status: not complete as a production maintenance program. Cleanup, wrong-owner phrase repair, and broad metadata backfill remain controlled maintenance topics.
- Production apply status: no production cleanup, backfill, or phrase-repair apply has been run or approved by this checkpoint.
- Known deferred work: feature-local obsolete selectors, broad legacy CSS cleanup, light `on-primary` contrast decision, physical iOS safe-area verification, release-time browser smoke checks, and first-run confirmation that the updated `Protected Baseline CI` GitHub Actions workflow passes after push.

This is a release-candidate checkpoint, not a production data-repair signoff.

## 2. Latest Protected Baseline

Latest verified commands and counts on 2026-06-29:

- Full PHPUnit: `96 tests, 1090 assertions` via `./vendor/bin/phpunit` in the dev webserver container.
- Frontend mounted tests: `1` file, `2` tests passed via `npm run test:frontend -- --reporter=verbose`.
- Focused REG-008 Reader/Review static guard: `1 test, 28 assertions`.
- `npm run check:migration`: passed.
- Production build: passed through `npm run check:migration`; Vite transformed `749` modules.
- `npm run check:css`: passed with `0` errors and `386` warning-only legacy CSS debt entries.
- `node scripts/check-dark-theme-contrast.js`: passed.
- `git diff --check`: passed.
- Browser verification scope: Review question/reveal/correct/error/progress/toolbar/transition states; Reader normal text, selected-vs-hover distinction, status colors, side panel, stage buttons, toolbar, and mobile 390px bottom sheet; light smoke for Review and Reader selected-word state. Prior protected browser scope also includes representative desktop, narrow/tablet, mobile, and targeted 320px checks across Admin tables/tabs, Vocabulary, Library chapters, dialogs, Reader Settings, sidebar/drawer/bottom navigation, manual routing, and Home responsive geometry.

The latest full PHPUnit count is `1090` assertions. Older summary text that listed `1088` assertions is superseded by this checkpoint.

## 3. Completed User-Visible Migration Areas

- Vocabulary token validation: centralized token classification, SQL/PHP parity, safe cleanup reporting, and invalid-token guardrails are implemented and tested.
- Vocabulary filtering: book/chapter filtering handles current IDs, stale or missing metadata fallbacks, distinct books, phrase fallbacks, and stale frontend responses.
- Chapter pagination/statistics: server pagination, All mode, stable totals, complete visible/all statistics, and footer overlay positioning are protected.
- Dialogs and Reader Settings: viewport-aware dialogs, metadata chips, current Vuetify model/activator usage, and responsive Reader Settings rows are verified.
- Table actions: shared compact action buttons preserve 32x32 geometry across representative tables.
- Reader/Review toolbars: shared vertical toolbar buttons preserve 40x40 circular geometry and readable icon states.
- Sidebar/mobile navigation: desktop sidebar, mobile drawer, bottom navigation, and bottom controls are aligned and theme-safe.
- Home dashboard overflow: mobile goal-card overflow is fixed and protected.
- User manual link: Vocabulary import links to the current Markdown manual route and anchor.
- Dark theme completed clusters: tables/pagination/headings; forms/selects/cards/menus/overlays; tabs/navigation active states; calendar/date-picker; and Reader/Review state colors.

## 4. Remaining Non-Blocking Or Deferred Work

| Item | Classification | Release blocker? | Notes |
| --- | --- | --- | --- |
| Feature-local obsolete selectors such as Admin API settings | Post-release follow-up | No | Audit one feature at a time and replace only selectors with runtime impact. |
| Broad legacy CSS cleanup | Post-release follow-up | No | Keep converting warning debt into scoped, evidence-backed batches; do not claim broad cleanup complete yet. |
| Light `on-primary` contrast decision | Product/design decision | No | Current primary/white ratio is documented as 3.23:1; changing palette or foreground affects broad UI. |
| Physical iOS safe-area verification | Post-release follow-up | No | Chromium structure was checked; physical iOS/Safari remains unverified. |
| Wrong-owner phrase repair apply packet | Maintenance operation | No for code RC | Pending explicit approval, fresh backup, matching dry-run, and rollback criteria. Do not treat as already applied. |
| Invalid-token cleanup apply | Maintenance operation plus product policy | No for code RC | Cleanup apply remains blocked pending exact policy and candidate approval. |
| Broad metadata backfill | Maintenance operation, prohibited currently | No for code RC | Broad apply remains prohibited; do not run it as part of release candidate. |
| Release-time browser smoke checks | Release validation | Recommended before tagging | Review, Reader, Library chapters, Vocabulary, and Home should remain in the final smoke list. |
| First `Protected Baseline CI` GitHub Actions run | CI validation | Yes before final release tagging | The first run exposed missing Vite manifest ordering before PHPUnit. The workflow now builds and asserts `public/build/manifest.json` before PHPUnit; RC readiness still depends on the updated workflow passing on GitHub after push. |

## 5. Recommended Next Execution Order

1. Run the final release smoke checklist against the release-candidate build.
2. Push and confirm the updated `Protected Baseline CI` GitHub Actions workflow passes with `public/build/manifest.json` created before PHPUnit.
3. Audit feature-local Admin API selectors as a scoped post-release or pre-tag polish task if time allows.
4. Optionally verify physical iOS safe-area behavior for sidebar/mobile drawer bottom controls.
5. Execute wrong-owner phrase repair only after explicit approval, fresh backup, matching final dry-run, and rollback readiness.
6. Prepare any exact-token cleanup packet only after product policy approves specific candidates.

## 6. Working Tree And Artifacts

Current `git status --short` before creating the original checkpoint was clean. The follow-up CI stabilization adds the workflow and documentation files listed below.

Current checkpoint-relevant changed files:

- `release-notes/migration-release-candidate-checkpoint-2026-06-29.md`
- `.github/workflows/ci.yml`
- `tests/README.md`
- `release-notes/ci-workflow-stabilization-2026-06-29.md`

CI follow-up status:

- The first protected workflow failed because PHPUnit ran before Vite generated `public/build/manifest.json` in a clean checkout.
- `.github/workflows/ci.yml` now runs `npm run production` and `test -s public/build/manifest.json` before `./vendor/bin/phpunit`.
- The protected `npm run check:migration` step remains in place and still runs its production build contract.

Package and generated artifact status:

- `package-lock.json`: unchanged.
- Tracked production build artifacts: no tracked build artifact changes remain from verification.
- Temporary containers: no one-off audit container is required for this checkpoint; PHPUnit used disposable `docker compose run --rm` webserver containers.
- Temporary databases/test data: no new checkpoint-specific data maintenance apply was run. Prior browser audit data was confined to the development database.
- Production data: not touched. No cleanup, backfill, phrase repair, or production maintenance apply was run.

## Release-Candidate Decision

Proceed with a migration release-candidate build from the current code/test baseline after the updated `Protected Baseline CI` GitHub Actions workflow passes, while keeping production data repair and deferred design/CSS cleanup explicitly outside the release-candidate readiness claim.
