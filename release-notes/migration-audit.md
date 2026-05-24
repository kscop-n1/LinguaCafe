# Migration Audit

Date: 2026-05-24

This file is an iterative audit of the current LinguaCafe migration state. It records verified regressions and migration leftovers that still need an actionable fix plan.

## Resolved in releases 0.5.6-0.5.24

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
- The root frontend now has a tracked `package-lock.json`, so the shipped dependency graph is reproducible instead of floating without a lockfile.
- The remaining Vuetify 2 table/tab shims (`v-tabs-items`, `v-tab-item`, `v-simple-table`) were removed from the app bootstrap, and no current source still references them.
- The dead Vuetify 2 selection shims (`v-list-item-group`, `v-list-item-avatar`, `v-list-item-content`) were removed from the app bootstrap, and no current source still references them.

The verified issues below remain the active open audit surface.

## Verified issues

Some entries below have since been fixed in releases 0.5.6-0.5.8 and are kept here for audit provenance. The unresolved active surface is the remaining set after the resolved section above.



### 3. The codebase still depends on Vuetify 2 compatibility shims

Evidence:
- `resources/js/app.js:9-18` defines `currentTheme` and `breakpoint` shims on the Vuetify instance.
- `resources/js/components/Login/LoginForm.vue:18-25` still uses legacy Vuetify props like `outlined`, `rounded`, and `depressed`.
- `resources/js/components/Home/Home.vue:54-83` and many other components still use `outlined`, `depressed`, `dark`, `filled`, and `dense`.

Impact:
- The migration is not complete; the app is still carrying some compatibility behavior instead of a clean Vuetify 3 component model.
- The remaining legacy props can still cause warnings and subtle behavior differences when newer components are mixed with older markup patterns.

### 4. Password migration can force existing users into a change-password flow

Evidence:
- `database/migrations/2023_11_01_224016_modify_users_table_2.php:12-19` adds `password_changed` with a default of `false`.
- `app/Http/Controllers/UserController.php:26-30` exposes that flag to the frontend.
- `resources/js/components/Home/Home.vue:9-34` shows the password-change gate when `passwordChanged` is false.
- `app/Services/UserService.php:37-40` only flips the flag to true when the user changes password later.
- Live DB queried from the running webserver container shows a single user row (`id` 9) with `is_admin = 0`, `password_changed = 0`, and `created_at` / `updated_at` timestamps from 2026-05-23.

Impact:
- Existing users who were migrated into the new schema can be marked as needing a password change even if that was not intended for them.
- This matches the reported password regression after migration.
- If the database migration was applied to an existing installation, the lack of a data backfill makes this a behavior change, not just a schema change.

### 6. A separate Vue 3 / PrimeVue app tree exists, but the build does not use it

Evidence:
- `resources/vue3/src/main.js:1-35` bootstraps a separate Vue app with PrimeVue components and Tailwind-oriented styling.
- `resources/vue3/package.json:1-20` defines its own PrimeVue/Vite dependency set and lockfile.
- `vite.config.js:6-23` still points the Laravel build at `resources/js/app.js`, not `resources/vue3/src/main.js`.

Impact:
- The repository contains two frontend directions at once: the deployed build path and an alternate Vue 3/PrimeVue tree.
- This is a direct inconsistency between "old code that remained in the app" and "new components started to be applied".
- Any work done in `resources/vue3` can drift from the actual shipped app unless the build entrypoint is switched or the tree is removed.

### 7. Upgrade docs claim the migration is complete, but the code still shows migration leftovers

Evidence:
- `UPGRADE_MIGRATION_PHASES.md:8-16` says phases 1-5 are complete and Vue 3 / Vuetify 3 migration is complete.
- `UPGRADE_AUDIT_REPORT.md:7-21` repeats that the phases are implemented and compatibility mode is removed.
- `resources/js/app.js:9-18` and `resources/js/app.js:69-89` still add compatibility shims for old Vuetify APIs and old components.

Impact:
- The documentation and codebase are out of sync.
- This makes the current migration state harder to trust and hides the real cleanup surface needed for an actionable plan.
- The audit needs to treat the docs as historical claims, not as proof that cleanup is finished.





### 15. Several auth screens still use the old Bootstrap layout instead of the migrated Vue shell

Evidence:
- `resources/views/auth/register.blade.php:1` extends `layouts.app`.
- `resources/views/auth/passwords/reset.blade.php:1` extends `layouts.app`.
- `resources/views/auth/passwords/email.blade.php:1` extends `layouts.app`.
- `resources/views/auth/passwords/confirm.blade.php:1` extends `layouts.app`.
- `resources/views/auth/verify.blade.php:1` extends `layouts.app`.
- `resources/views/layouts/app.blade.php:1-21` is a plain Bootstrap-oriented shell without the `theme-color` handling used by `layouts.user.blade.php`.

Impact:
- Login/home use the new Vue shell, but other auth flows still render through an older Bootstrap layout, so the app presents multiple UI generations side by side.
- These screens bypass the same theming and component migration path, which makes the overall auth experience inconsistent.
- The mixed layout strategy increases maintenance burden and makes it harder to reason about where theme and component bugs originate.


## Actionable clusters

1. Theme bootstrap and theme chooser
- Fix the split theme source of truth, the broken Auto selection path, and the live OS-following gap.
- Reconcile server-side theme defaults, login/home bootstrap, and the localStorage/cookie contract.
- Rework the theme chooser so it stores real theme keys and not numeric indexes.

2. Theme settings and text styling
- Make the theme editor handle `auto` and `eink` explicitly instead of collapsing them into light.
- Remove the debug console noise in theme/text flows.
- Clean up the text-styling editor so the UI, the sample preview, and the saved state all target the same theme.

3. Legacy component migration cleanup
- Replace or retire the remaining Vuetify 2 shims in app.js and the screens that depend on them.
- Convert legacy selection and tab wrappers to real Vuetify 3 patterns where the behavior matters.
- Remove obsolete props and patterns that are still carried through migrated components.

4. Auth and user-state regressions
- Resolve the password flag behavior so existing users are not forced into an unintended password-change state.
- Make auth screens consistent with the migrated shell or explicitly isolate them if they must stay legacy.
- Verify the live DB state against expected migrated-user semantics.

5. Deployment/runtime hygiene
- Fix the startup readiness race between webserver and DB bootstrap.
- Clean up runtime warnings and leftover console logs so browser verification is useful again.
- Reconcile the shipped frontend with the documentation and the alternate `resources/vue3` track.

## Open audit gaps

- I have not yet run a browser console verification against the deployed app in `/home/serhii/docker/linguacafe`.
- I have not yet mapped every remaining legacy Vuetify 2 component usage to a specific cleanup task.
- I have not yet audited the deployed database state to confirm whether the password flag regression affected existing users in production data.

