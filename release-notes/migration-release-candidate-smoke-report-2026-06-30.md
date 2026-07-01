# Migration Release-Candidate Smoke Report - 2026-06-30

Smoke verification executed on 2026-07-01 against commit `614d9a1c3cbd061dadd78a4efa0693224a775049` (`main`, matching `origin/main` in the local checkout).

Sources:

- `release-notes/migration-release-candidate-checkpoint-2026-06-29.md`
- `release-notes/ci-workflow-stabilization-2026-06-29.md`
- `release-notes/regression-reconciliation-tracker-2026-06-17.md`
- `release-notes/regression-stabilization-summary-2026-06-20.md`

## Preconditions

- Latest commit pushed: yes. Local `main` was at `614d9a1` and `git status --short --branch` reported `## main...origin/main` with no divergence.
- Protected Baseline CI: passed. Latest GitHub Actions run:
  - Workflow: `Protected Baseline CI`
  - Run: `28403011003`
  - Event: `push`
  - Branch: `main`
  - Commit message: `Build Vite assets before PHPUnit in CI`
  - Status: `completed`
  - Conclusion: `success`
  - Started: `2026-06-29T21:12:15Z`
- Working tree before report creation: clean.

Note: a direct sandboxed `git ls-remote origin refs/heads/main` probe could not resolve `github.com`, but GitHub Actions status was read through `gh run list`, and the local branch tracked `origin/main` without divergence.

## Automated Baseline

| Check | Result |
| --- | --- |
| GitHub Actions `Protected Baseline CI` | Passed, run `28403011003` |
| Full PHPUnit | Passed: `96 tests, 1090 assertions` |
| Frontend mounted tests | Passed: `1` file, `2` tests |
| `npm run check:migration` | Passed; includes dependency check, hard legacy check, and production build |
| Production build | Passed separately through `npm run production`; Vite transformed `749` modules |
| `npm run check:css` | Passed with `0` errors and `386` warning-only legacy CSS debt entries |
| `node scripts/check-dark-theme-contrast.js` | Passed |
| `git diff --check` | Passed |

## Runtime

Browser smoke verification used an isolated local dev runtime:

- URL: `http://127.0.0.1:8181`
- Webserver container: `linguacafe-rc-smoke-webserver`
- Existing dev services: `linguacafe-database-dev`, `linguacafe-redis-dev`, `linguacafe-python-service-dev`
- Disposable local smoke data only: user `75`, book `31`, chapter `122`

Production data was not queried or mutated. No cleanup apply, metadata backfill apply, phrase repair apply, or production maintenance command was run.

## Browser Smoke Results

### Home

- Dashboard loaded at desktop, mobile, and 320px widths.
- Goal cards did not create horizontal overflow:
  - 1440px sample: `clientWidth=1432`, `scrollWidth=1432`
  - 390px sample: `clientWidth=382`, `scrollWidth=382`
  - 320px sample: `clientWidth=312`, `scrollWidth=312`
- Calendar/date picker opened on Home and exposed month/year/day controls. It remained readable and navigable.
- Light and dark theme samples were checked during the smoke pass.

### Vocabulary

- Vocabulary page loaded with five smoke rows: `amigo`, `hola`, `lectura`, `mundo`, `stir-crazy`.
- Book filter opened, listed `Any` and `RC Smoke Book`, and selecting `RC Smoke Book` retained the expected five book-backed rows.
- Controls and table remained readable with no horizontal overflow.
- Import dialog opened through `Data -> Import`.
- Import dialog user manual link opened `/user-manual/Setup#Importing%20Vocabulary%20into%20LinguaCafe` and showed the expected manual section.

### Library / Chapters

- Book list loaded and displayed `RC Smoke Book`.
- Chapters page loaded and displayed `RC Smoke Chapter`.
- Chapter statistics stayed visible and stable: total `5`, unique `5`, known `1`, highlighted `3`, new `1`.
- Pagination footer menu opened with `10`, `25`, `50`, and `All`.
- Selecting `25`, `50`, and `All` preserved the chapter row and statistics.
- Footer/dropdown overlay did not block the table content in the checked states.

### Reader

- Chapter `122` opened at `/chapters/read/122`.
- Text was readable in light and dark samples.
- Smoke words rendered, including `stir-crazy`.
- Selecting `hola` opened the details panel with the word and dictionary/translation area.
- Reader toolbar buttons measured `40x40` in both light and dark samples.
- Mobile and desktop samples had no horizontal overflow.

### Review

- Review page opened at `/review`.
- Question and reveal states were readable.
- Correct path advanced the review count and next card.
- Incorrect path (`Again`) remained readable and advanced without overflow.
- Review toolbar buttons measured `40x40`.

### Admin

- Admin access was verified with the disposable admin smoke user.
- Users page loaded with table, action icon, pagination, and the smoke user row.
- Languages page loaded with installable-language rows and action buttons.
- Dictionaries page loaded with dictionary table and pagination.
- Font Types page loaded with font table and upload action.
- API page loaded with forms and toggles.
- Reviews page loaded with SRS form rows and save action.
- Dark theme sample on Admin Reviews remained readable with no horizontal overflow.

### Navigation

- Desktop sidebar active/inactive navigation was visible.
- Mobile `More` drawer opened and exposed Home, Library, Vocabulary, Review, User settings, User manual, Admin settings, Logout, Hide, Theme, and Language.
- Mobile bottom navigation was present at 390px and included Home, Library, and Vocabulary.
- Bottom controls Hide/Theme/Language appeared in the drawer and desktop sidebar.

## Widths And Themes

Checked representative screens at:

- Desktop: about 1440px
- Tablet/narrow: 900px
- Mobile: 390px
- Narrow risk sample: 320px Home

Checked theme coverage:

- Light theme: Home, Vocabulary, Library/Chapters, Reader
- Dark theme: Reader, Review, Admin Reviews, Home mobile/narrow samples

## Defects Found

No release-blocking regressions were found.

### Post-Release Follow-Up

- `npm run check:css` still reports `386` warning-only legacy CSS debt entries. This matches the known checkpoint debt and is not a release blocker because the check has `0` errors.
- Physical iOS/Safari safe-area behavior remains unverified, matching the deferred item in the checkpoint.

### Unrelated Existing Issue

- During isolated webserver startup, `npm install` reported `3` npm audit vulnerabilities (`1 low`, `2 high`). This was not part of the requested protected baseline gate and was not surfaced by the smoke interactions, but it should be handled separately if dependency audit policy requires it.

## Final Recommendation

The current build can be tagged as the migration release candidate from a code, CI, automated-check, and browser-smoke perspective.

The recommendation excludes production maintenance operations. Cleanup apply, metadata backfill apply, and phrase repair apply remain separate approval-gated data-maintenance tasks.
