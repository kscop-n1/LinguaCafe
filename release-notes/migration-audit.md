# Migration Audit

Date: 2026-05-24

This file is an iterative audit of the current LinguaCafe migration state. It records verified regressions and migration leftovers that still need an actionable fix plan.

## Resolved in releases 0.5.6-0.5.8

The following live regressions were fixed and browser-verified after the initial audit was written:
- Theme bootstrap and auto-mode handling now stay in sync across cookie, localStorage, and the active Vuetify theme.
- The webserver entrypoint now waits for the database socket before running migrations and seeding, which removes the startup connection-refused noise.
- Existing migrated users were backfilled out of the unintended password-change gate, while new admin-created users still keep the intended flow.
- The theme selection dialog now uses native Vuetify 3 list items instead of the legacy list-group shim, while keeping auto/light/dark/eink selection behavior intact.
- The vocabulary filter menus now use plain Vuetify 3 list items instead of the legacy list-group wrapper, while preserving the existing active-state styling and selection logic.
- The home calendar popup now uses native Vuetify 3 window items instead of the legacy tabs-items shim, while keeping the info/edit toggle behavior intact.
- The admin settings layout now uses native Vuetify 3 window items instead of the legacy tabs-items shim, while keeping the tab switching behavior intact.

The verified issues below remain the active open audit surface.

## Verified issues

Some entries below have since been fixed in releases 0.5.6-0.5.8 and are kept here for audit provenance. The unresolved active surface is the remaining set after the resolved section above.

### 1. Theme source of truth is split between cookie and localStorage

Evidence:
- `app/Http/Controllers/HomeController.php:24-33` reads `$_COOKIE['theme']` for the home page bootstrap.
- `app/Http/Controllers/UserController.php:45-58` reads `$_COOKIE['theme']` for the login page bootstrap.
- `resources/js/components/Dialogs/ThemeSelectionDialog.vue:66-79` writes theme changes to localStorage, not cookie.
- `resources/js/components/Layout.vue:291-307` and `resources/js/components/Layout.vue:331-332` resolve theme from localStorage and then persist it back to localStorage.

Impact:
- The server-rendered entry points and the client-rendered shell do not share one canonical theme source.
- Login and home page theme can drift from the selected theme after a refresh or first render.
- This is a direct cause of the color/theme inconsistency reported after the migration.

### 2. Auto theme is only partially implemented

Evidence:
- `resources/js/components/Layout.vue:248-250` has the OS theme watcher commented out.
- `resources/js/components/Layout.vue:291-307` only applies auto theme during initial selection / load.
- `resources/js/services/ThemeService.js:78-98` only reads the stored auto flag and current media query state; it does not subscribe to changes.

Impact:
- The "auto" theme behaves like a one-time selection, not a live OS-following mode.
- Users can end up with stale colors after system theme changes.

### 3. The codebase still depends on Vuetify 2 compatibility shims

Evidence:
- `resources/js/app.js:9-18` defines `currentTheme` and `breakpoint` shims on the Vuetify instance.
- `resources/js/app.js:69-89` defines replacement components for `v-tabs-items`, `v-tab-item`, `v-simple-table`, `v-list-item-group`, `v-list-item-avatar`, and `v-list-item-content`.
- `resources/js/components/Dialogs/ThemeSelectionDialog.vue:13-18` still uses `v-list-item-group`, `v-list-item-avatar`, and `v-list-item-content`.
- `resources/js/components/Login/LoginForm.vue:18-25` still uses legacy Vuetify props like `outlined`, `rounded`, and `depressed`.
- `resources/js/components/Home/Home.vue:54-83` and many other components still use `outlined`, `depressed`, `dark`, `filled`, and `dense`.

Impact:
- The migration is not complete; the app is carrying a compatibility layer instead of a clean Vuetify 3 component model.
- This is a likely source of warnings and subtle behavior differences when newer components are mixed with older markup patterns.

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

### 5. Theme settings UI is still internally inconsistent

Evidence:
- `resources/js/components/UserSettings/ThemeSettings/UserSettingsThemes.vue:145-155` only exposes light and dark theme editing.
- `resources/js/services/ThemeService.js:11-18` and `resources/js/themes.js:1-70` still support `eink`.
- `resources/js/components/UserSettings/ThemeSettings/UserSettingsThemes.vue:164-205` has repeated `level1` keys in `wordStyling`, so only the last duplicate survives.

Impact:
- Theme customization coverage is incomplete relative to the themes the app can actually load.
- Duplicate keys mean part of the text-styling state is silently overwritten, which is a migration-era inconsistency rather than a deliberate config shape.

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

### 8. Dependency reproducibility is split across two frontend tracks

Evidence:
- `git -C /data/git/LinguaCafe ls-files -- package-lock.json resources/vue3/package-lock.json` only returns `resources/vue3/package-lock.json`, so the alternate Vue 3 tree is locked while the shipped root frontend is not.
- `resources/vue3/package-lock.json` is tracked, but the deployable root frontend still resolves dependencies from `package.json` without a tracked lockfile.

Impact:
- The repo can drift into different dependency graphs between the shipped app and the alternate Vue 3 tree.
- This makes component version comparisons, migration validation, and reproducible builds harder.
- It increases the chance that a fresh install or CI run will produce behavior that differs from the deployed stack.

### 9. Legacy selection shims drop real group behavior in core menus

Evidence:
- `resources/js/app.js:82-89` defines `v-list-item-group` as a plain `v-list` wrapper and `v-list-item-avatar`/`v-list-item-content` as div wrappers.
- `resources/js/components/Dialogs/ThemeSelectionDialog.vue:12-20` relies on `v-list-item-group v-model="selectedTheme"` for theme selection.
- `resources/js/components/Vocabulary/Vocabulary.vue:55-178` uses `v-list-item-group` in every filter menu (stage, book, chapter, translation, phrases, order by).

Impact:
- The shim does not preserve Vuetify group selection behavior, so keyboard navigation, roving selection, and native active-state handling are not available.
- The code compensates by hand with click handlers and manual active classes, which is a migration inconsistency and a maintenance trap.
- Core theme selection and vocabulary filters are built on the legacy wrapper instead of a real Vuetify 3 equivalent.

### 10. Login and home pages disagree on the default server-side theme

Evidence:
- app/Http/Controllers/UserController.php:45-58 falls back to theme cookie or light for the login page.
- app/Http/Controllers/HomeController.php:24-40 falls back to theme cookie or dark for the home page.

Impact:
- The first rendered view can flip between light and dark depending on whether the user is on /login or an authenticated page.
- This is a visible migration-era inconsistency even before localStorage/theme JS runs.
- It increases the chance of a theme flash or wrong initial colors on fresh sessions with no stored theme cookie.

### 11. Websocket app key is duplicated as a hardcoded frontend constant

Evidence:
- resources/js/vuex/Shared.js:11-18 hardcodes the Echo/Pusher key as wjp2pou6ebgibtwccqsj.
- config/broadcasting.php:35-40 and config/reverb.php:68-76 also default to the same key through env fallbacks.

Impact:
- The browser bundle is coupled to a specific websocket app key instead of reading it from the server or env at runtime.
- If the deployment key changes, the frontend must be rebuilt to match the backend config.
- This is another example of old/new infrastructure being mixed rather than centralized cleanly after the migration.

### 12. Theme settings page collapses auto and eink into the light-theme editor

Evidence:
- `resources/js/components/UserSettings/ThemeSettings/UserSettingsThemes.vue:145-155` initializes `selectedTheme` with `ThemeService.getCurrentTheme() === 'dark' ? 'dark' : 'light'`.
- `resources/js/components/UserSettings/ThemeSettings/UserSettingsThemes.vue:146-155` only exposes `light` and `dark` in the selectable theme list.
- `resources/js/components/UserSettings/ThemeSettings/UserSettingsThemes.vue:297-309` saves only `light` and `dark` theme colors.

Impact:
- When the current theme is `auto` or `eink`, the editor silently opens the light theme instead of the active theme.
- Users can think they are editing the active theme while actually modifying the light theme only.
- This is a functional migration regression in the color settings flow, not just an incomplete theme list.

### 13. Login page ignores user-specific theme color settings

Evidence:
- `app/Http/Controllers/HomeController.php:24-43` passes `themeSettings` from `SettingsService` into the home page view.
- `app/Http/Controllers/UserController.php:45-58` passes an empty `themeSettings` object into the login page view.
- `resources/js/components/Layout.vue:278-287` only loads custom theme colors when `this.$props.themeSettings?.vuetifyThemes` exists.

Impact:
- Custom color settings are applied on the authenticated home shell but not on the login page.
- A user who customized colors will still see defaults on `/login`, which creates a visible color inconsistency across the same account.
- This is a direct migration regression in theming behavior, not just a cosmetic difference.

### 14. Several core screens cache theme locally and do not react to live theme switches

Evidence:
- `resources/js/components/Home/Calendar.vue:176` caches `theme` from localStorage in `data()`.
- `resources/js/components/Home/Home.vue:105` caches `theme` from localStorage in `data()`.
- `resources/js/components/Library/Library.vue:175` caches `theme` from localStorage in `data()`.
- `resources/js/components/Review/Review.vue:365` caches `theme` from localStorage in `data()` and uses it for child `theme` props and transition timing.
- `resources/js/components/TextReader/TextReader.vue:238` caches `theme` from localStorage in `data()` and uses it to drive layout differences such as eink-specific rendering.

Impact:
- Switching theme through the global dialog updates the shell Vuetify theme, but these screens can keep using the old cached theme value until they are reloaded.
- This creates partial theme application and makes the migration feel inconsistent even when the global theme selector appears to work.
- The bug is especially visible on review and text-reader screens where theme affects rendering logic, not just colors.

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

### 16. Root route is defined twice and the first definition returns only the Laravel version

Evidence:
- `routes/web.php:16-18` defines `GET /` as a closure returning `['Laravel' => app()->version()]`.
- `routes/web.php:34-100` later defines another `GET /` inside the authenticated web group pointing to `HomeController@index`.

Impact:
- The app has two competing root handlers in one file, which is a routing inconsistency created or preserved during the migration.
- The first root definition can mask the real home shell route, or at minimum makes the routing intent unclear and fragile.
- This is an easy-to-miss functional issue because it sits outside the frontend migration code but directly affects the app's main entrypoint.

### 17. Webserver boot races MySQL readiness and logs connection-refused errors during migrate/seed

Evidence:
- Live `docker compose logs --tail 120` for `/home/serhii/docker/linguacafe/docker-compose.yml` shows `linguacafe-webserver` hitting `SQLSTATE[HY000] [2002] Connection refused` while running migration bootstrap against `mysql` on startup.
- The same startup logs then continue with `Nothing to migrate` and database seeding once the DB is reachable, which means the app container is trying to bootstrap before the database is reliably ready.

Impact:
- Deploy/startup is fragile and timing-dependent even when the stack eventually comes up.
- Boot-time migrate/seed noise makes runtime verification noisier and can hide real migration regressions.
- If the startup timing shifts, the same sequence can turn into an actual failed boot instead of just a noisy retry.

### 18. Theme selection dialog stores numeric indexes, so only the light option behaves correctly

Evidence:
- `resources/js/components/Dialogs/ThemeSelectionDialog.vue:13-15` renders the options from `displayNames` as an indexed `v-for` and calls `selectTheme(index)` on click.
- `resources/js/components/Dialogs/ThemeSelectionDialog.vue:66-78` saves the clicked value directly to localStorage, but the handler only has string checks for `'auto'`, `'dark'`, and `'eink'`.
- The first item in the rendered list is `auto`, so all four visible choices are reduced to numeric indexes before the theme logic runs.

Impact:
- The chooser no longer preserves theme identity across clicks, because it stores `0`, `1`, `2`, or `3` instead of `auto`, `light`, `dark`, or `eink`.
- `light` still lands on the default light path by accident, but `auto`, `dark`, and `eink` never hit their intended branches.
- This is a user-visible regression in the main theme migration UI and explains why theme changes can appear to ignore the selected option.

### 19. Production runtime still emits debug console logs in core theme and text paths

Evidence:
- `resources/js/components/Layout.vue:293-300` logs `auto dark` and `auto light` while choosing the active theme.
- `resources/js/components/UserSettings/ThemeSettings/UserSettingsTextStyling.vue:572-573` keeps a `console.log` helper for the current text-styling object.
- `resources/js/components/Text/TextBlockGroup.vue:1384-1389` logs `apiDefinitions` while building vocabulary box API data.

Impact:
- The migrated app still emits avoidable console noise in core runtime paths.
- This makes browser console verification harder and violates the stated goal of getting the post-migration app back to a clean, warning-free runtime.
- Because these logs sit in theme and text rendering flows, they are part of the user-facing migration surface, not dead test code.


### 20. Server-rendered theme-color meta ignores eink and falls back to the light chrome

Evidence:
- `resources/views/layouts/user.blade.php:6-11` only special-cases `dark`; every non-dark theme uses the light `theme-color` meta.
- `app/Http/Controllers/UserController.php:45-58` and `app/Http/Controllers/HomeController.php:24-40` both pass the requested theme string into the shared user layout.
- Live `/login` with `Cookie: theme=eink` still renders `<meta name="theme-color" content="#F2F3F5" />`, which is the light chrome color.

Impact:
- The eInk theme is not represented in the server-rendered browser chrome, even when the cookie says `eink`.
- This creates another visible theme mismatch during first paint and makes the migrated theme story feel incomplete.
- It is separate from the theme editor bug because it affects the HTML bootstrap itself, not just the settings UI.


### 21. Live webserver points at an external MySQL host while the compose file still declares the bundled mysql service as the dependency

Evidence:
- `/home/serhii/docker/linguacafe/docker-compose.yml:10-21` declares `depends_on: mysql` and defaults `DB_HOST` to `linguacafe-database` for the bundled service.
- `docker inspect linguacafe-webserver` shows the running container is actually configured with `DB_HOST=host.docker.internal` and `DB_PORT=3308`.
- `docker inspect linguacafe-database` shows the bundled mysql container is `exited unhealthy`, so the declared compose dependency is not the database the live webserver is actually using.

Impact:
- The documented deployment topology does not match the live runtime topology, so compose-level assumptions are unreliable during debugging.
- Any migration or boot-time reasoning based on the bundled mysql service can be wrong in the deployed stack.
- This makes the deployment harder to verify and obscures whether failures originate in the app, the external DB, or the stale compose definition.


### 22. Text reader Anki snackbars do not apply an explicit theme in eink mode

Evidence:
- `resources/js/components/Text/TextBlockGroup.vue:23-24` only binds `:light` when the theme is `light` and `:dark` when the theme is `dark`.
- `resources/js/components/Layout.vue:99` and `resources/js/components/Layout.vue:176-180` treat `eink` as a first-class theme for shell/background handling.
- Because `eink` is neither `light` nor `dark`, the snackbar falls back to Vuetify default styling instead of being explicitly themed like the rest of the shell.

Impact:
- Text-reader notifications can visually diverge from the active eInk shell theme.
- This is a component-level migration gap in a core reading flow, not just a generic global theme concern.
- It is another sign that migrated theme handling is still partial and inconsistent across components.


### 23. ThemeService applies eink colors but still activates Vuetify as light

Evidence:
- `resources/js/services/ThemeService.js:11-17` maps `themeName === 'eink'` to `defaultThemes.eink` for the light theme slot, but then calls `setActiveTheme(..., themeName === 'dark' ? 'dark' : 'light')`.
- `resources/js/services/ThemeService.js:41-49` only exposes `dark` or `light` as the active Vuetify theme name.
- `resources/js/components/Layout.vue:176-184` separately branches on `this.theme === 'eink'` for shell background colors, so shell styling and Vuetify's active theme can disagree.

Impact:
- `eink` is rendered as a color palette overlay on top of a light active theme rather than as a first-class active theme.
- This can leave component-level Vuetify states and shell-level theme state out of sync in the same view.
- The mismatch is part of the theme migration surface and explains why some components still behave like light theme even when the shell says `eink`.


### 24. Review screen ignores saved false values for several vocabulary popup settings

Evidence:
- `resources/js/components/Review/Review.vue:382-390` initializes `vocabularyHoverBox`, `vocabularyHoverBoxSearch`, and `vocabularyBottomSheet` with `DefaultLocalStorageManager.loadSetting(...) || true`.
- `resources/js/components/Review/Review.vue:200-205`, `resources/js/components/Review/Review.vue:251-254`, and `resources/js/components/Review/Review.vue:319-322` pass those settings through to the text renderer on every review screen variant.
- Because `false || true` evaluates to `true`, any saved `false` value is discarded during component initialization.

Impact:
- Users cannot reliably disable the hover box, hover-box search, or bottom-sheet vocabulary UI in review mode.
- The review screen silently overrides persisted preferences on load, so the settings UI lies about the effective runtime state.
- This is a functional regression independent of theme color handling; it affects review behavior and persisted user preferences directly.



### 25. Vue Router 4 navigation guards still read currentRoute with Vue Router 3 assumptions

Evidence:
- `package.json:31-37` depends on `vue-router` `^4.6.4`.
- `resources/js/components/Admin/AdminSettingsLayout.vue:81-85` compares `this.$router.currentRoute.fullPath` against the target admin URL before pushing.
- `resources/js/components/Library/Library.vue:259-276` does the same for the books route.
- `resources/js/components/Vocabulary/Vocabulary.vue:467-491` compares `this.$router.currentRoute.path` against the computed vocabulary search URL before pushing.

Impact:
- These guards use the Vue 2 / router-3 access pattern instead of the Vue Router 4 ref-based route state, so the duplicate-navigation checks are not reliable.
- Users can get redundant pushes/replaces and navigation warning noise in admin, library, and vocabulary flows.
- This is a real runtime mismatch introduced or preserved during the migration, not just a style issue.



### 26. User-facing admin, library, and import flows still emit debug console logs

Evidence:
- `resources/js/components/Text/VocabularySearchBox.vue:214-217` logs `data[dictionaryIndex]` during search processing.
- `resources/js/components/Admin/AdminDictionarySettings.vue:244-249` logs the loaded dictionary list after fetch.
- `resources/js/components/Library/Import/ImportSource/JellyfinSubtitleList.vue:149-154` logs unsupported language codes in the import flow.
- `resources/js/components/Library/EditBookDialog.vue:165-170` logs image-change events during book editing.

Impact:
- Browser console verification is still noisy in everyday admin, library, and import workflows.
- These logs are user-facing and not limited to theme or text styling code paths, so they represent a separate runtime hygiene regression.
- The remaining console noise makes it harder to distinguish real migration errors from debug output.



### 27. User manual TOC navigation also reads Vue Router 4 state with Vue Router 3 assumptions

Evidence:
- `resources/js/components/UserManual/UserManual.vue:47-52` loads the current manual page from `this.$route.params.currentPage`.
- `resources/js/components/UserManual/UserManual.vue:83-94` builds `currentPath` from `this.$router.currentRoute.path` and compares it against the clicked manual page before pushing.
- `resources/js/components/UserManual/UserManual.vue:83-94` also preserves the hash in the path comparison, so the route check depends on the same stale access pattern as the other router mismatches.

Impact:
- The table-of-contents selection logic can no longer reliably tell whether the manual is already on the selected page.
- That makes manual navigation noisier and more fragile than it should be after the router migration.
- This is a distinct user-facing regression in the manual reader, not just another duplicate guard in the main app shell.



### 28. Language selection dialog also uses stale Vue Router 4 route access

Evidence:
- `resources/js/components/Dialogs/LanguageSelectionDialog.vue:95-100` compares `this.$router.currentRoute.fullPath` against `/admin/languages` before navigating.
- The project depends on `vue-router` `^4.6.4`, so route state is a ref-based API rather than the old Vue Router 3 shape.
- The dialog is a separate UI flow from the admin settings layout, so this is a distinct occurrence in a different user action.

Impact:
- Opening the language management dialog can trigger redundant navigation or warning noise because the current-route check is not reading router state the way Vue Router 4 expects.
- This adds another migration-era navigation mismatch in a user-facing dialog rather than a core page shell.
- The app still has multiple stale router access points that need cleanup before the migration can be considered stable.


### 29. Review page can throw on load because `backgroundColor` reads `currentThemeColors.foreground` during data initialization

Evidence:
- Live browser reload of `https://lingua.lan/review` reproduces `TypeError: Cannot read properties of undefined (reading 'foreground')` from the built `app-0o8TmGEI.js` bundle.
- `resources/js/components/Review/Review.vue:376-381` initializes `backgroundColor: this.currentThemeColors.foreground` inside `data()`.
- The shared `currentThemeColors` value is a computed mixin property from `resources/js/app.js`, so it is not safe to dereference during component data initialization.

Impact:
- The review screen can fail during mount instead of rendering a usable page, which is a direct runtime regression in a core user flow.
- This is separate from the persisted-settings boolean bug already recorded for the review screen: this one is a mount-time crash caused by an unsafe initialization path.
- Because the crash happens before the main review UI is usable, browser verification on the deployed stack still needs this code path fixed before the review flow can be trusted.


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

