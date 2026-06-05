# Regression Analysis Backlog - 2026-06-05

Purpose: track reported post-migration regressions before investigation and fixes. Work through these one by one, compare current fork with the old local upstream checkout where useful, and record root cause before patching.

Current fork checkout: `/data/git/LinguaCafe`
Old/original reference checkout: `/data/git/old_LinguaCafe/LinguaCafe`
Production stack boundary: `/home/serhii/docker/linguacafe` is production only; do not hotfix there.

## Investigation rules

- Do not treat symptoms as CSS-only until the active component, API response, and browser behavior are checked.
- Compare against `/data/git/old_LinguaCafe/LinguaCafe` for migration regressions.
- For Firefox/mobile-only issues, verify in Firefox or an equivalent browser path when available; Chromium-only checks are not enough.
- Do not store production cookies, sessions, or XSRF tokens in notes. Keep only endpoint/status/symptom evidence.
- If a regression exposes a static migration pattern, update `scripts/check-legacy.js` or another relevant check.

## Issues

### 1. Side menu Language icon missing

Status: reported, not investigated.

Symptom: after migration, the Language icon next to the Language side-menu item does not render at all.

Initial suspected areas:

- `resources/js/components/Layout.vue`
- language flag/icon component usage after Vuetify migration
- icon/image asset path or slot rendering in navigation button prepend area
- CSS alignment rules added for `.navigation-language-button`

Next analysis steps:

- Inspect current Language side-menu DOM and computed styles.
- Compare current implementation with old checkout.
- Verify whether the icon node is absent, hidden, zero-sized, or failing to load.

### 2. Side Menu > Language opens an empty modal

Status: reported, not investigated.

Symptom: clicking Side Menu > Language opens a modal, but the modal content is empty.

Initial suspected areas:

- Language dialog component import/registration after migration
- Vuetify dialog/list rendering changes
- language data loading API/store path
- slot/content migration residue

Next analysis steps:

- Reproduce click path and check console/network.
- Identify modal component and whether it receives language options.
- Compare old checkout dialog markup and data flow.

### 3. Admin Settings language icons are visually broken

Status: reported, not investigated.

Symptom: in Admin Settings tabs `Languages` and `Dictionaries`, language icons render in an ugly/inconsistent way.

Initial suspected areas:

- admin language/dictionary table components
- shared language icon/flag component
- Vuetify chip/avatar/icon sizing migration
- CSS for flag images in dense tables/cards

Next analysis steps:

- Inspect affected admin screens in light/dark themes.
- Compare icon markup and sizing with old checkout.
- Decide whether the fix belongs in a shared icon component or admin-specific table layout.

### 4. Vocabulary mobile Firefox horizontal overflow/sidebar

Status: reported, not investigated.

Symptom: on mobile Firefox, the Vocabulary page still gets a side horizontal bar during navigation between pages; something breaks layout width.

Initial suspected areas:

- Vocabulary page root/container width
- mobile bottom navigation/drawer layout
- table/list pagination controls
- viewport units or `min-width` rules that behave differently in Firefox
- recently changed book-filter UI or word uniqueness UI

Next analysis steps:

- Reproduce on mobile Firefox viewport and record `document.documentElement.scrollWidth` vs `clientWidth`.
- Binary search suspicious wide elements with DOM measurements.
- Compare Chromium vs Firefox behavior.

### 5. API page broken on mobile Firefox

Status: reported, not investigated.

Symptom: specifically on mobile Mozilla Firefox, API data is not shown/rendered; desktop is OK.

Initial suspected areas:

- Admin/API settings page layout and responsive tables/cards
- Firefox-specific CSS parse/overflow behavior
- API data request blocked or hidden by layout only on mobile
- Vuetify expansion/tab rendering on mobile

Next analysis steps:

- Reproduce in mobile Firefox and separate network failure from render/layout failure.
- Check console and request statuses.
- Compare desktop Firefox, mobile Firefox, and Chromium mobile viewport.

### 6. Admin Settings > Dashboard backup creation returns 401

Status: reported, not investigated.

Evidence summary: `GET https://lingua.lan/backups/create` from `https://lingua.lan/admin/dashboard` returned HTTP 401 in Firefox. Request was same-origin XHR. Full cookies/tokens intentionally not stored.

Initial suspected areas:

- backup route auth/middleware after migration
- admin permission check or current-user admin detection
- route method mismatch or missing CSRF/session handling
- API client base URL / credentials / CORS defaults, especially suspicious `access-control-allow-origin: http://localhost:3000` on production response

Next analysis steps:

- Inspect Laravel routes/controller/middleware for `/backups/create`.
- Compare old checkout route/controller behavior.
- Reproduce as an admin user and inspect Laravel logs for authorization failure reason.
- Verify whether backup creation should be POST instead of GET or needs admin guard/session refresh.

### 7. User Settings > Theme UI is still strange

Status: reported, partially mitigated in v0.5.39, needs broader analysis.

Symptom: User Settings > Theme has a very strange UI and looks like migration-related layout issues remain.

Initial suspected areas:

- `resources/js/components/UserSettings/ThemeSettings/*`
- migrated Vuetify grid/form controls
- slider/select/checkbox row layout
- shared theme editor CSS and mobile handling

Next analysis steps:

- Inventory all Theme tabs/sections, not only Text section.
- Compare old checkout structure and intended grouping.
- Identify shared layout component opportunity instead of one-off CSS fixes.

### 8. Vocabulary book filter does not work after word uniqueness changes

Status: reported, not investigated in this backlog.

Symptom: book filter on Vocabulary page does not work at all after the approach to word uniqueness changed.

Initial suspected areas:

- Vocabulary query/filter API
- word uniqueness schema/migration
- relationship between words, texts/books, chapters, and reviews
- frontend book filter state binding and request payload
- backend query grouping/deduplication that may discard book context

Next analysis steps:

- Compare expected old behavior using old checkout.
- Inspect DB schema and current vocabulary API query.
- Verify whether the frontend sends selected book IDs and whether backend applies them before/after uniqueness grouping.
- Add regression test for filtering by book after root cause is found.

### 9. Non-word tokens are saved as vocabulary words

Status: reported, not investigated.

Examples reported: `#`, `'s`, `):`, `+1`, `+10`, `+10d6`, `+1d`, `+1d10`, `+1d20`, `+1d3`, `+1d4`, `+1d6`, `+1d8`, `+2`, `+2/+4`, `+20`, `+2d`, `+2d10`, `+2d6`, `+3`, `+30`, `+3d6`, `+4`, `+4/+8`, `+40`, `+4d6`, `+5`, `+50`, `+5d6`.

Initial suspected areas:

- tokenizer / word extraction pipeline
- Python tokenization service rules
- backend import/highlight word validation
- migration changed parser regex or filtering threshold
- existing feature gap if original app also allowed these tokens

Next analysis steps:

- Compare tokenizer behavior against old checkout for the same text sample.
- Identify whether invalid tokens are created during import, highlighting, vocabulary save, or review generation.
- Define language-aware rules for what counts as a word without breaking contractions or non-Latin languages.
- Add tests for punctuation-only, dice/math tokens, possessives, and valid words.

## Suggested order

1. Language side-menu icon and empty modal, because they likely share one component/data path.
2. Vocabulary book filter, because it is a functional data bug and may affect user trust most.
3. Backup creation 401, because it is admin functionality and may involve auth/middleware.
4. Non-word vocabulary tokens, because it needs careful tokenizer rules and tests.
5. Mobile Firefox Vocabulary overflow and API page rendering, because they need browser-specific reproduction.
6. Admin language icons and remaining Theme UI polish, because they are visual regressions and may share component-level fixes.
