# Post-Deploy RC UI Fixes - 2026-07-01

## Scope

This pass covered the post-deploy `v0.6.0-rc1` UI and content regressions observed during Docker runtime smoke testing:

- Built-in app release notes and home version text still showed the older release.
- Book chapters action buttons rendered inconsistently; a first follow-up attempt also incorrectly narrowed the chapters table to the summary card width, which cramped/clipped the Chapter, New, and Actions columns during Docker validation.
- Reader hover vocabulary popups could be clipped near the top of the reader panel.
- Reader dictionary translation cards were cramped because the searched word shared the same tight title line as the dictionary/source label.

No production data cleanup, backfill, phrase repair, tokenizer changes, classifier changes, or broad reader behavior changes were made.

## Root Cause

- App-visible release notes are not generated from `release-notes/*.md`; they are rendered from `resources/js/components/Home/PatchNotes.vue`. The Docker RC documentation existed, but the built app content had not been updated.
- `resources/js/components/Home/Home.vue` also had a hardcoded displayed version string.
- The migrated chapters page needs the summary card and the chapters table card to be sized independently. The summary card can keep its natural/narrower width, but the chapters table needs enough responsive page width for the Chapter, New, and Actions columns. The first post-deploy CSS follow-up forced `.chapters-card` to `800px`, which matched the summary card but reintroduced horizontal table scrolling/clipping on desktop.
- Reader hover positioning still used old page-coordinate assumptions. After the Vue/Vuetify migration, the scrollable reader panel is the relevant clipping container, so top-space checks needed to be relative to that container.
- Dictionary search result titles kept the old fixed one-line/float layout, which made the source title and original word compete for the same visual line.

## Changed Files

- `resources/js/components/Home/PatchNotes.vue`
  - Added `v0.6.0-rc1` app-visible release candidate notes.
- `resources/js/components/Home/Home.vue`
  - Updated the displayed current version to `v0.6.0-rc1`.
- `resources/sass/Library/BookChapters.scss`
  - Corrected the first follow-up by letting the chapters card fill the available page width up to `1180px` instead of matching the upper summary card's `800px` width.
  - Scoped chapter table cell padding so desktop widths can show Chapter, New, and Actions without page overflow.
  - Kept the Chapter column wrapping while keeping compact numeric/action columns on one line.
  - Scoped the real chapter icon action buttons to stable `32px` dimensions without shrinking read-count/status pills.
- `resources/js/components/Text/TextBlockGroup.vue`
  - Made hover vocabulary popup placement relative to the reader scroll container and clamped/flipped it inside the visible reader area.
- `resources/sass/Text/VocabularySearchBox.scss`
  - Changed dictionary result titles to wrapping flex layout.
  - Styled the searched/original word as a wrapping inline pill.
  - Changed definitions to normal left-aligned wrapped text with stable padding.

## Verification Evidence

Static and build checks passed:

```bash
node scripts/check-dark-theme-contrast.js
npm run check:css
npm run check:migration
npm run test:frontend
git diff --check
VERSION=local-rc-ui docker compose build webserver
```

`npm run check:css` still reports the existing warning inventory, but finished with `0 Errors` and passed the critical CSS legacy check.

Docker runtime smoke used a temporary webserver container from `ghcr.io/kscop-n1/linguacafe-webserver:local-rc-ui` on port `8183`, connected to the existing development MySQL/Redis/Python services:

```bash
docker compose -f /tmp/linguacafe-rc-ui-compose.yml up -d --force-recreate
docker exec linguacafe-rc-ui-webserver test -s /var/www/html/public/build/manifest.json
curl -I http://127.0.0.1:8183/login
docker compose -f /tmp/linguacafe-rc-ui-compose.yml logs --tail 80 webserver
```

Observed runtime results:

- `/login` returned `HTTP/1.1 200 OK`.
- The rebuilt image contained `public/build/manifest.json`.
- Startup logs showed `Nothing to migrate`, seeders completed, and Apache, backup, Horizon, and Reverb entered `RUNNING` state.
- Browser verification of `/patch-notes` showed `v0.6.0-rc1` as the first app-visible release note.
- Browser measurement on `/books/31` showed the summary and chapters cards both rendered at `800px` on desktop and `900px` viewports, collapsed together to `358px` at a `390px` mobile viewport, and kept chapter action buttons at `32px` by `32px`.
- The chapters page had no document-level horizontal overflow at `1280px`, `900px`, or `390px`; wide table content stayed inside the table wrapper scroll area.
- Browser hover verification on `/chapters/read/122` showed the top-word and middle-word hover popups inside the reader container with `clipped=false` and no document-level horizontal overflow.
- Browser translation-panel verification used a temporary dev-only custom dictionary fixture for `hola` and `stir-crazy` so the real dictionary result-card DOM rendered source title, original word, and translations together. The fixture was removed after verification.
- In light theme, the result-card title computed as `display:flex` and `flex-wrap:wrap`, the original word rendered as a separate wrapping pill, and long definitions wrapped as left-aligned normal text with no document overflow.
- In dark theme, the same result-card layout used dark readable colors (`rgb(236, 236, 241)` text and a dark word-pill background) with no document overflow.
- At a `390px` mobile viewport, the Reader used `#vocab-bottom-sheet` (`display:flex`), the long `stir-crazy` result stayed inside the bottom sheet, and long translation text wrapped cleanly with no document overflow.

### 2026-07-02 Chapters Hotfix Correction

Docker production validation caught that the first chapters-width follow-up was wrong: forcing `#books .book.detailed.chapters-card` to `800px` made the chapters table match the upper summary card, but it cramped the Chapter/New/Actions columns and caused desktop table-internal scrolling. The corrected behavior keeps the summary card independent and lets the chapters table card use responsive page width up to `1180px`.

Final Docker browser verification used `ghcr.io/kscop-n1/linguacafe-webserver:local-rc-hotfix` on port `8184`, with the rebuilt production manifest present and Apache, backup, Horizon, and Reverb running. The test fixture used a temporary dev-only smoke user/book/chapter/dictionary and did not run production cleanup, backfill, phrase repair, tokenizer, or classifier changes.

Book chapters measurements from the final Docker image:

- `900x900`: document `clientWidth=900`, `scrollWidth=900`; chapters card `868px`; table/wrapper `842px`; Chapter cell `clientWidth=224`, `scrollWidth=224`, `clipped=false`; Chapter/New/Actions headers all visible; footer `Items per page / 1-1 of 1` visible; Read and Actions icon buttons both `32x32`; read-count/status pill separately measured `72x32`.
- `1280x900`: document `clientWidth=1280`, `scrollWidth=1280`; chapters card `992px`; table/wrapper `966px`; Chapter/New/Actions visible; footer visible; Read and Actions icon buttons both `32x32`.
- `1440x900`: document `clientWidth=1440`, `scrollWidth=1440`; chapters card `1152px`; table/wrapper `1126px`; Chapter/New/Actions visible; footer visible; Read and Actions icon buttons both `32x32`.
- `390x900`: document `clientWidth=390`, `scrollWidth=390`; chapters card `358px`; table `794px` inside wrapper `348px`; left scroll position shows Chapter without page overflow; right scroll position shows New/Actions with Read and Actions icon buttons both `32x32`; footer remains visible and usable.

Screenshots saved during verification:

- `/tmp/linguacafe-rc-hotfix-final-900.png`
- `/tmp/linguacafe-rc-hotfix-final-1280.png`
- `/tmp/linguacafe-rc-hotfix-final-1440.png`
- `/tmp/linguacafe-rc-hotfix-final-390.png`

Previous RC fixes were rechecked in the same final Docker build:

- `/patch-notes` showed `v0.6.0-rc1` as the first app-visible release note.
- Reader hover popup for top/middle smoke words stayed inside the reader area with `clipped=false` and no document-level horizontal overflow.
- Dark theme translation popup for the long `stir-crazy` dictionary result had normal wrapping, readable `rgb(236, 236, 241)` text, no card overflow, and no document overflow.
- Light theme translation popup for the same long result had normal wrapping, readable `rgb(45, 45, 45)` text, no card overflow, and no document overflow.

Final automated checks passed: `npm run test:frontend`, `npm run check:migration`, `npm run check:css` (`0 Errors`, `393 Warnings`), `node scripts/check-dark-theme-contrast.js`, full PHPUnit in the dev container (`96 tests`, `1090 assertions`), and `git diff --check`.

Because `v0.6.0-rc1` was already used for a Docker image that contained the bad chapters-width follow-up, the corrected Docker release should use a new RC tag, recommended as `v0.6.0-rc2`.

## Status

The post-deploy UI/content regressions listed above are fixed locally and verified against a production-style Docker image.

The RC release recommendation is now `v0.6.0-rc2`, because `v0.6.0-rc1` validation exposed a chapters table regression that required this correction.

## Deferred Follow-Up

- The smoke database did not contain dictionary entries for the RC smoke words. This pass used a temporary dev-only custom dictionary fixture for stronger browser verification, then removed it. A committed seeded fixture would make this check repeatable without ad hoc DB setup.
- No production cleanup, phrase repair, or tokenizer/classifier validation was run in this pass by design.
