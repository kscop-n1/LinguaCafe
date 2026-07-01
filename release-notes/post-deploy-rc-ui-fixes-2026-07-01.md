# Post-Deploy RC UI Fixes - 2026-07-01

## Scope

This pass covered the post-deploy `v0.6.0-rc1` UI and content regressions observed during Docker runtime smoke testing:

- Built-in app release notes and home version text still showed the older release.
- Book chapters action buttons rendered inconsistently and the chapters table card no longer matched the summary card width.
- Reader hover vocabulary popups could be clipped near the top of the reader panel.
- Reader dictionary translation cards were cramped because the searched word shared the same tight title line as the dictionary/source label.

No production data cleanup, backfill, phrase repair, tokenizer changes, classifier changes, or broad reader behavior changes were made.

## Root Cause

- App-visible release notes are not generated from `release-notes/*.md`; they are rendered from `resources/js/components/Home/PatchNotes.vue`. The Docker RC documentation existed, but the built app content had not been updated.
- `resources/js/components/Home/Home.vue` also had a hardcoded displayed version string.
- The migrated chapters page widened only the table card to `1180px`, while the old app kept the summary and chapters content aligned at the same `800px` width.
- Reader hover positioning still used old page-coordinate assumptions. After the Vue/Vuetify migration, the scrollable reader panel is the relevant clipping container, so top-space checks needed to be relative to that container.
- Dictionary search result titles kept the old fixed one-line/float layout, which made the source title and original word compete for the same visual line.

## Changed Files

- `resources/js/components/Home/PatchNotes.vue`
  - Added `v0.6.0-rc1` app-visible release candidate notes.
- `resources/js/components/Home/Home.vue`
  - Updated the displayed current version to `v0.6.0-rc1`.
- `resources/sass/Library/BookChapters.scss`
  - Restored chapters card width to match the old app's aligned `800px` content width.
  - Scoped chapter action buttons to stable `32px` icon dimensions.
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

## Status

The post-deploy UI/content regressions listed above are fixed locally and verified against a production-style Docker image.

The RC release recommendation does not change by this note alone: GHCR `v0.6.0-rc1` images still need to be published through the release workflow before external Docker installs can pull the RC tag.

## Deferred Follow-Up

- The smoke database did not contain dictionary entries for the RC smoke words. This pass used a temporary dev-only custom dictionary fixture for stronger browser verification, then removed it. A committed seeded fixture would make this check repeatable without ad hoc DB setup.
- No production cleanup, phrase repair, or tokenizer/classifier validation was run in this pass by design.
