# LinguaCafe Migration Action Plan

Date: 2026-05-24
Source: [migration-audit.md](./migration-audit.md)

## Goal
Stabilize the post-migration stack so the deployed app works without theme drift, component contract warnings, password regressions, or deployment/runtime mismatches.

## Workstreams

### 1. Theme bootstrap and theme chooser
- Make `theme` a single source of truth across cookie, localStorage, server bootstrap, and the Vue shell.
- Fix `ThemeSelectionDialog` so it stores and restores real theme keys instead of numeric indexes.
- Restore live `auto` behavior with an OS theme watcher instead of one-time selection.
- Align login and home server defaults so the first paint is consistent.
- Add explicit `eink` handling to server-rendered chrome instead of falling back to light.

### 2. Theme settings and text styling
- Make the theme editor expose and edit `light`, `dark`, `auto`, and `eink` explicitly.
- Stop collapsing `auto` and `eink` into the light-theme editor.
- Remove remaining debug `console.log` calls in theme/text flows.
- Validate that the sample preview, saved settings, and runtime theme application use the same theme key.

### 3. Component migration cleanup
- Replace the remaining Vuetify 2 compatibility shims in `resources/js/app.js`.
- Convert legacy selection and tab wrappers to real Vuetify 3 patterns where user interaction matters.
- Remove deprecated props and component patterns that still trigger mixed-era code paths.
- Fix review-screen boolean settings so saved false values are respected on reload.
- Guard review-page initialization so `backgroundColor` does not dereference `currentThemeColors` before the mixin computed property is ready.
- Check the migrated screens for stale state caches that should react to live theme changes.

### 4. Auth and password state
- Fix the password-change gate so migrated users are not forced into an unintended flow.
- Verify how `password_changed` is seeded, backfilled, and consumed in the live database.
- Keep auth screens consistent with the migrated shell, or isolate them intentionally if they must stay on the legacy layout.
- Re-test the password update path end to end on the deployed stack.

### 5. Deployment/runtime hygiene
- Bring the compose definition back in line with the actual runtime DB topology.
- Remove startup readiness races between the app container and DB bootstrap.
- Clean up browser console noise so runtime verification is meaningful again.
- Remove remaining debug console logs from admin, library, and import flows.
- Reconcile the shipped frontend with the `resources/vue3` track and the upgrade docs.

## Suggested execution order
1. Fix theme bootstrap and theme chooser.
2. Fix password state and auth shell regressions.
3. Remove remaining legacy component shims.
4. Clean up deployment/runtime mismatches.
5. Verify browser console and first-paint behavior on the live stack.

## Verification gates
- Login and home should render the same intended theme state for `light`, `dark`, `auto`, and `eink`.
- Theme changes should not produce Vue warnings or console noise in the browser.
- Existing users should not be forced into password-change flow unless explicitly intended.
- Core screens should react correctly to theme changes without requiring reloads.
- The live deployment topology should match the documented compose/runtime assumptions.
