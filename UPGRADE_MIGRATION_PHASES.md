# LinguaCafe Phased Migration Progress

Updated: 2026-05-23
Source audit: `UPGRADE_AUDIT_REPORT.md`

## Current Summary

Phases 1-5 have now been implemented and verified at build/backend-test/basic-browser-smoke level. Phase 5 now runs on native Vue 3 without `@vue/compat`.

| Phase | Status | Current Result |
|---|---|---|
| Phase 1: Backend Security And Runtime Stabilization | Complete | Composer audit clean; backend dependencies patched within PHP 8.2-compatible constraints |
| Phase 2: Smoke-Test Coverage Before Frontend Migration | Complete | Reader, vocabulary edit, chapter read marker, and review/SRS endpoint flows covered by PHPUnit; Docker update checklist added |
| Phase 3: Frontend Security Isolation | Complete | `vue-showdown`/`showdown` removed; user manual uses local markdown renderer; production npm audit clean |
| Phase 4: Build Tool Migration | Complete | Laravel Mix/Webpack removed; Vite build pipeline active through Blade `@vite` and `vite.config.js` |
| Phase 5: Vue 3 / Vuetify 3 Migration | Complete | Runtime moved to native Vue 3, Vue Router 4, Vuex 4, and Vuetify 3; `@vue/compat` removed |

## Phase 1: Backend Security And Runtime Stabilization

Status: Complete

Completed:

| Item | Status | Evidence |
|---|---|---|
| Composer platform pinned to Docker runtime PHP | Complete | `composer.json` has `config.platform.php = 8.2.31` |
| Laravel 11 patch/minor dependency refresh | Complete | `composer.lock` updated |
| Reverb security update | Complete | `composer.lock` updated |
| Tinker/PsySH advisory fixed | Complete | `composer audit --locked` reports no advisories |
| Symfony dependency compatibility corrected | Complete | PHP 8.2-compatible Symfony packages resolved |
| MySQL-based PHPUnit smoke run | Complete | Full dev MySQL test suite passes |

## Phase 2: Smoke-Test Coverage Before Frontend Migration

Status: Complete

Covered in `tests/Feature/MigrationSmokeTest.php`:

| Flow | Status |
|---|---|
| Vocabulary word can be updated after creation | Complete |
| Reader opens an existing processed book/chapter | Complete |
| Chapter read marker persists and is returned in chapter list | Complete |
| Review queue returns a due word scoped to book/chapter | Complete |
| Correct review answer persists SRS stage increase | Complete |
| Missed review answer persists SRS stage decrease/relearning state | Complete |
| Docker update with existing DB/storage | Checklist complete in `DOCKER_UPDATE_CHECKLIST.md` |

Remaining limitation:

| Flow | Status | Reason |
|---|---|---|
| Reader hover/selection does not accidentally trigger SRS level-up | Manual/browser follow-up recommended | The backend SRS smoke test passes, but this exact guard is frontend interaction state in `TextBlockGroup.vue` and needs real E2E coverage for full confidence |

## Phase 3: Frontend Security Isolation

Status: Complete

Completed:

| Item | Status | Evidence |
|---|---|---|
| Remove `vue-showdown` and `showdown` | Complete | Removed from `package.json`; global registration removed |
| Replace user manual markdown rendering | Complete | `UserManual.vue` uses local escaped renderer |
| Production dependency audit | Complete | `npm audit --omit=dev` returns `found 0 vulnerabilities` |

## Phase 4: Build Tool Migration

Status: Complete

Completed:

| Item | Status | Evidence |
|---|---|---|
| Replace Laravel Mix scripts | Complete | `package.json` uses Vite for dev/build |
| Remove Mix/Webpack config | Complete | `webpack.mix.js` removed; Mix/Webpack packages removed |
| Add Vite config | Complete | `vite.config.js` uses `laravel-vite-plugin` and Vue plugin |
| Move asset loading to Blade `@vite` | Complete | Layouts load `resources/js/app.js` through Vite |
| Production build | Complete | `npm run production` passes |

Known follow-up:

| Item | Reason |
|---|---|
| Sass deprecation warnings remain | Bootstrap 4 and app SCSS still use Sass `@import`/legacy functions. They are warnings, not current build failures. Fixing them should be a separate Bootstrap/Sass cleanup, not mixed into this migration. |

## Phase 5: Vue 3 / Vuetify 3 Migration

Status: Complete

Completed:

| Item | Status | Evidence |
|---|---|---|
| Vue runtime upgraded | Complete | `vue` 3.x installed without `@vue/compat`; app bootstraps through `createApp` |
| Router upgraded | Complete | `vue-router` 4.x with `createRouter`/`createWebHistory` |
| Store upgraded | Complete | `vuex` 4.x with `createStore` |
| Vuetify upgraded | Complete | `vuetify` 3.x with `createVuetify` |
| CommonJS frontend bootstrap removed | Complete | `resources/js/app.js` and `bootstrap.js` use ESM imports |
| Vue2 lifecycle hooks migrated | Complete | `beforeDestroy` replaced by `beforeUnmount` |
| `.sync` table options migrated | Complete | Replaced with `v-model:options` |
| Old `$vuetify.breakpoint/currentTheme` usage removed | Complete | `Layout.vue` uses local display/theme bridge |
| Old Vuetify CSS link removed | Complete | `resources/views/layouts/user.blade.php` no longer loads `/css/vuetify.min.css` |
| Login page browser smoke | Complete | `agent-browser` reports `mounted: true`, `error: null` on `/login` using non-compat Vite asset |
| Production npm audit | Complete | `npm audit --omit=dev` returns `found 0 vulnerabilities` |

## Verification Commands

Latest verified commands:

| Command | Result |
|---|---|
| `docker run --rm -v /data/git/LinguaCafe:/app -w /app composer:2 composer audit --locked` | Pass: no security vulnerability advisories found |
| `npm audit --omit=dev` | Pass: found 0 vulnerabilities |
| `npm run production` | Pass: Vite build succeeds; Sass warnings remain |
| `docker compose -f /data/git/LinguaCafe/docker-compose-dev.yml run --rm --no-deps webserver php artisan test` | Pass: 10 tests, 36 assertions |
| `agent-browser` smoke on local `/login` | Pass: app bootstrap `mounted: true`, `error: null`, asset `app-CBXC2rtg.js` built without `@vue/compat` |

## Release Recommendation

This branch is now suitable for a migration release candidate. Before tagging a public release, do a manual browser pass on login, library, reader, vocabulary edit, review, settings, and user manual using a copied/staging database.
