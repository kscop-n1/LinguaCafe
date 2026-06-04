# Mobile UI Regression Root-Cause Plan

Created: 2026-06-05

Scope: compare the current Vue 3 / Vuetify 3 LinguaCafe checkout in `/data/git/LinguaCafe` with the pre-migration checkout in `/data/git/old_LinguaCafe/LinguaCafe`, then track the remaining mobile UI regression fixes from the annotated feedback.

## Root Cause Summary

The feedback is valid as a migration-regression class, not as isolated screenshot positioning problems.

The common root causes are:

- Vuetify 2 generated DOM and utility classes changed in Vuetify 3, especially `v-stepper`, `v-data-table`, `v-switch`, `v-slider`, `v-bottom-navigation`, `v-navigation-drawer`, and overlay/menu components.
- Several old layouts depended on fixed heights or narrow fixed widths that only worked with the old component internals, for example 380px goal cards, 60-64px word-count pills, 44px reader toolbar, and 8/4 column switch rows.
- Some partial fixes exist in the current code, but a few are incomplete or need browser verification because the markup and CSS no longer target the same generated elements.
- The affected areas share reusable layout patterns: dashboard cards, setting rows, numeric value pills, status chips, and toolbar buttons. Fixes should update those patterns rather than one viewport-specific coordinates.

## Evidence From Old vs Current Code

- `resources/js/components/Home/Goal.vue`: old code used a `v-spacer` between card text and actions; current code removed that spacer and added `.goal-body`, `.goal-description`, and `.goal-actions`, which is the right direction but still needs mobile verification.
- `resources/sass/Home/Home.scss`: old `.goal` had fixed `height: 380px`; current `.goal` uses `min-height: 400px`, flexible body, minimum description height, and bordered actions.
- `resources/js/components/Layout.vue`: current bottom sidebar language item still has only `navigation-button`; current Sass includes `.navigation-language-button` selectors that are not wired into the language item.
- `resources/sass/Library/ImportDialog.scss`: old code had no Vuetify 3 stepper structural compensation; current code adds `.v-stepper-item`, `.v-stepper-step`, `.v-stepper-item__content`, and mobile header wrapping.
- `resources/sass/Library/Books.scss` and `BookChapters.scss`: old highlighted/new word pills used fixed widths around 60-64px; current code uses `min-width: 76px`, `width: auto`, and `white-space: nowrap`.
- `resources/js/components/Library/BookChapters.vue`: current code added a `Read` column and read-count chip, which restores the missing finished count concept, but its mobile behavior and theme treatment still need verification.
- `resources/js/components/TextReader/TextReaderSettings.vue`: old help icons were in the right control column next to switches; current code moved several help icons to the label column. The remaining 8/4 mobile grid still makes switches feel detached.
- `resources/sass/TextReader/TextReader.scss`: old settings rows used fixed `height: 50px/60px`; current code uses `min-height`, taller slider rows, label help spacing, and dark fullscreen toolbar colors.
- `resources/sass/app.scss`: current code adds global Vuetify 3 slider thumb-label, slider hover-surface, and switch theme styles. This fixes contrast/hover classes globally but still needs page-level spacing validation.
- `resources/js/components/UserSettings/ThemeSettings/UserSettingsTextStyling.vue`: current code already adds scoped grid layout for text setting rows and mobile fallback at 700px; this should be strengthened and verified, not replaced with screenshot offsets.

## Trackable Fix Plan

### 1. Verification Baseline

- [ ] Start the local dev runtime from `/data/git/LinguaCafe` using the known dev-server setup.
- [ ] Verify the current code at desktop width and mobile width before editing, in both light and dark themes.
- [ ] Capture browser console output while opening the affected pages/modals.
- [ ] Use the same screens after each fix batch: home, sidebar, import modal, book page, chapter table, reader settings modal, reader page fullscreen/dark, user settings theme text editor.

### 2. Home Dashboard Goal Cards

Status from code comparison: partially mitigated, still needs mobile verification.

- [ ] Keep the current flexible card structure in `Goal.vue`; do not restore the old spacer-only layout.
- [ ] Make `#goals` use responsive card sizing such as `flex: 1 1 292px` with a max width where needed, instead of relying only on fixed width.
- [ ] Ensure `.goal-body` can grow and `.goal-actions` is always separated by a border and aligned at the bottom.
- [ ] Verify the Reviews placeholder button does not introduce a hidden focus target or mobile title misalignment.

### 3. Sidebar Bottom Language Control

Status from code comparison: valid remaining root cause.

- [ ] Add a dedicated `navigation-language-button` class to the language item in `Layout.vue`.
- [ ] Align flag and label through a shared list-item prepend/content rule, not through per-language margins.
- [ ] Add bottom safe spacing using drawer append padding plus `env(safe-area-inset-bottom)` where appropriate.
- [ ] Verify full drawer and rail drawer states at desktop/tablet/mobile breakpoints.

### 4. Import Modal Stepper

Status from code comparison: partially mitigated, needs browser confirmation.

- [ ] Keep targeting Vuetify 3 classes in `ImportDialog.scss`; do not depend on old `v-stepper-step` only.
- [ ] Confirm the divider line sits at the icon/step centerline and never crosses text.
- [ ] If current flex header still crowds labels, convert the step header to a stable responsive grid/flex pattern with explicit label area and connector area.
- [ ] On narrow mobile widths, wrap or simplify the stepper header and hide connector lines rather than letting them overlap.

### 5. Book Summary Numeric Pills

Status from code comparison: partially mitigated, still needs shared fix.

- [ ] Keep `min-width`/`width: auto`/`white-space: nowrap` for highlighted/new word pills.
- [ ] Replace floated value alignment in `.book-info-table` with flex alignment so wider values do not clip or wrap ambiguously.
- [ ] Apply the same value-pill class to `Book.vue`, `BookListDetailed.vue`, and chapter table count pills to avoid divergent behavior.
- [ ] Verify large numbers such as `14,169`, percentages, and mixed display mode.

### 6. Chapter Finished Status Indicators

Status from code comparison: partially mitigated, needs theme/mobile verification.

- [ ] Keep the dedicated `Read` column and show the read count as readable text, for example `1x`, not an icon-only green chip.
- [ ] Style read-status chips with primary/foreground theme tokens, visible text, and consistent chip width.
- [ ] Ensure the chapter title column can shrink or wrap without colliding with the status column.
- [ ] Verify table behavior on mobile: horizontal scroll is acceptable; overlap is not.

### 7. Reader Settings Slider Rows

Status from code comparison: partially mitigated, still valid risk.

- [ ] Create or strengthen a reusable reader settings slider row style in `TextReader.scss`.
- [ ] Keep slider value bubbles compact and non-interactive; do not enlarge the thumb/overlay aggressively on hover.
- [ ] Reduce dependency on `thumb-size="38"` where it causes row overflow, or compensate with enough row height and top padding.
- [ ] Verify the first slider below the Font type dropdown, because that is the tightest layout.

### 8. Reader Settings Switch Rows

Status from code comparison: valid remaining root cause.

- [ ] Replace the repeated `cols="8" / cols="4" / justify-end` pattern with a reusable settings row that keeps labels, help icons, and switches visually associated.
- [ ] On mobile, allow the switch column to size to its content while the label column uses the remaining width.
- [ ] Keep global switch contrast styles in `app.scss`, but validate disabled, off, on, hover, and dark-theme states.

### 9. Reader Settings Help Icons

Status from code comparison: partially mitigated, needs completion.

- [ ] Keep help icons next to labels, not beside switches.
- [ ] Apply the label-with-help pattern consistently to Vocabulary sidebar, Vocabulary bottom sheet, Hover vocabulary box, and the auto-* options.
- [ ] Ensure labels wrap cleanly without pushing the switch into the edge of the modal.

### 10. Reader Page Dark Toolbar

Status from code comparison: partially mitigated, needs fullscreen/dark verification.

- [ ] Keep toolbar width at the old 44px baseline unless a responsive breakpoint deliberately changes it.
- [ ] Use theme foreground/text tokens for fullscreen dark toolbar surface and icons so it remains visible over black backgrounds.
- [ ] Verify hover/active states and tooltips in dark mode.
- [ ] Verify mobile top toolbar remains usable after any desktop vertical-toolbar changes.

### 11. User Settings Theme Text Editor

Status from code comparison: partially mitigated, needs hardening.

- [ ] Keep the scoped grid approach already added in `UserSettingsTextStyling.vue`.
- [ ] Convert repeated raw `.w-100` setting blocks into a clearer row/group class if needed, so labels, sliders, select controls, and checkboxes share spacing rules.
- [ ] Ensure the "For spaceless languages only" checkbox is visually attached to horizontal padding settings.
- [ ] Add mobile handling for checkbox groups and the color table, including horizontal scroll or stacked rows if needed.
- [ ] Verify the same layout with long labels such as "Wavy underline (removes borders)".

## Verification Matrix

- [ ] Light theme, desktop viewport.
- [ ] Dark theme, desktop viewport.
- [ ] Light theme, mobile viewport around 390x844.
- [ ] Dark theme, mobile viewport around 390x844.
- [ ] Home dashboard: goal cards and sidebar bottom controls.
- [ ] Library import modal: all stepper steps.
- [ ] Book page: loaded word counts, large numeric values, chapter table.
- [ ] Reader settings modal: Text, Vocabulary box, Vocabulary hover box tabs.
- [ ] Reader page: dark fullscreen toolbar and mobile toolbar.
- [ ] User settings > Themes > Text editor: all sliders, selects, checkboxes, and color table.
- [ ] Browser console has no new runtime errors during the checked flows.

## Validation Commands

- [ ] `npm run production`
- [ ] `npm run check:migration`
- [ ] `npm run check:css`
- [ ] `npm run test:php`
- [ ] `git diff --check`

## Automation Follow-Up

- [ ] Add a browser smoke test or scripted visual checklist if Playwright/agent-browser is stable for this project.
- [ ] Extend migration checks only for structural migration residue that can be detected statically, such as stale Vuetify 2 class selectors or old component props. Do not try to encode subjective visual spacing in `check:legacy`.


## Implementation Status for v0.5.39

Implemented in this release:

- [x] Responsive dashboard goal card sizing and action separation.
- [x] Sidebar Language item class wiring, alignment rules, and safe bottom spacing.
- [x] Vuetify 3 import stepper spacing hardening.
- [x] Shared larger numeric pill behavior for book/chapter word counts.
- [x] Theme-consistent chapter read-status chip treatment.
- [x] Reader settings slider thumb/row spacing and mobile switch-row layout.
- [x] Reader settings help icon placement retained next to labels with mobile wrapping.
- [x] Dark fullscreen reader toolbar contrast hardening.
- [x] User settings text-theme editor mobile row, checkbox group, and color table hardening.

Verified before release:

- [x] `npm run production`
- [x] `npm run check:migration`
- [x] `npm run check:css` critical check
- [x] `npm run test:php`
- [x] `git diff --check`
- [x] Browser console/page errors on local dev runtime for checked flows
- [x] Desktop and mobile DOM layout measurements for user settings text-theme editor
- [x] Sidebar Language control DOM/class/alignment measurement
