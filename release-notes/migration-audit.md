# Migration Audit

Date: 2026-05-24

This file is an iterative audit of the current LinguaCafe migration state. It records verified regressions and migration leftovers that still need an actionable fix plan.

## Resolved in releases 0.5.6-0.5.29

The following live regressions were fixed and browser-verified after the initial audit was written:
- Theme bootstrap and auto-mode handling now stay in sync across cookie, localStorage, and the active Vuetify theme.
- The webserver entrypoint now waits for the database socket before running migrations and seeding, which removes the startup connection-refused noise.
- Existing migrated users were backfilled out of the unintended password-change gate, while new admin-created users still keep the intended flow.
- The theme selection dialog now uses native Vuetify 3 list items instead of the legacy list-group shim, while keeping auto/light/dark/eink selection behavior intact.
- The vocabulary filter menus now use plain Vuetify 3 list items instead of the legacy list-group wrapper, while preserving the existing active-state styling and selection logic.
- The home calendar popup now uses native Vuetify 3 window items instead of the legacy tabs-items shim, while keeping the info/edit toggle behavior intact.
- The admin settings layout now uses native Vuetify 3 window items instead of the legacy tabs-items shim, while keeping the tab switching behavior intact.
- The text reader settings dialog now uses native Vuetify 3 window items instead of the legacy tabs-items shim, while keeping the tab switching behavior intact.
- The reader vocabulary box, sidebar, and bottom sheet now also use native Vuetify 3 window items instead of the legacy tabs-items shim, while keeping the reader word-selection flow intact.
- The user-settings theme color table now uses a native Vuetify 3 table instead of the legacy simple-table shim, while keeping the color editing flow intact.
- The user-settings text-styling panel now uses a native Vuetify 3 table instead of the legacy simple-table shim, while keeping the text styling controls intact.
- The admin language settings page now uses a native Vuetify 3 table instead of the legacy simple-table shim, while keeping the install flow intact.
- The book detail and book list tables now use native Vuetify 3 table markup instead of the legacy simple-table shim, while keeping the book info and navigation flow intact.
- The user-settings theme editor now uses the selected theme value directly in the Vuetify 3 selector, so stored light/dark/eink values are shown correctly instead of collapsing to light.
- The review screen now preserves saved false values for the hover box, hover-box search, and bottom-sheet vocabulary settings instead of coercing them back to true on load.
- The live compose file now matches the external MySQL deployment topology and no longer declares the bundled mysql service as the running webserver dependency.
- The review page now mounts cleanly without the initialization crash that previously read theme colors too early.
- The theme bootstrap now uses cookie-first resolution for both server and client paths, login and home default to light consistently, and auto mode resolves the system theme in the shell and theme service.
- The theme selection dialog now reloads the page after a theme change, so the authenticated shell and the screens that cache theme state reinitialize on switch.
- The websocket app key is now exported from the server-rendered layouts instead of being hardcoded in the frontend bundle.
- The legacy auth views now share the same cookie-based theme-color shell and render safely even though their old route names are not active in this build.
- The orphaned `resources/vue3` / PrimeVue app tree was removed from the repository, so there is no longer an alternate frontend track drifting from the shipped build path.
- The root frontend now has a tracked `package-lock.json`, so the shipped dependency graph is reproducible instead of floating without a lockfile.
- The remaining Vuetify 2 table/tab shims (`v-tabs-items`, `v-tab-item`, `v-simple-table`) were removed from the app bootstrap, and no current source still references them.
- The dead Vuetify 2 selection shims (`v-list-item-group`, `v-list-item-avatar`, `v-list-item-content`) were removed from the app bootstrap, and no current source still references them.
- The old `currentTheme` and `breakpoint` Vuetify adapter shims were removed from the bootstrap, leaving only the real Vuetify 3 theme/display APIs in use.
- The remaining legacy Vuetify 2 compatibility props were converted to native Vuetify 3 variants or removed, and no bare `outlined`, `depressed`, `filled`, `dense`, or `dark` attributes remain in `resources/js/components`.
- The upgrade docs now describe the migration as implemented but still with follow-up cleanup tracked in `release-notes/migration-audit.md`, instead of claiming the migration is fully complete.

All previously verified issues have been resolved in releases 0.5.6-0.5.28.
There is no active open audit surface remaining in this note.

