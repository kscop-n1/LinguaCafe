# LinguaCafe Upgrade Audit Report

Generated: 2026-05-22

Source plan: `UPGRADE_AUDIT_PLAN.md`

## Implementation Update - 2026-05-23

The original audit below is preserved as the baseline assessment. After implementation, the current migration status is tracked in `UPGRADE_MIGRATION_PHASES.md`.

Current verified result:

| Area | Result |
|---|---|
| Composer audit | Pass: no security vulnerability advisories found |
| NPM production audit | Pass: found 0 vulnerabilities |
| Production frontend build | Pass: Vite build succeeds; Sass deprecation warnings remain |
| PHPUnit on dev MySQL | Pass: 10 tests, 36 assertions |
| Browser smoke | Pass: /login bootstraps with native Vue 3/Vuetify 3, `mounted: true`, `error: null` |

Phases 1-5 are implemented. Phase 5 now runs without `@vue/compat`; Vue 3 compatibility mode has been removed from dependencies and Vite config.

## Executive Summary

The audit is complete enough to choose an upgrade strategy. The main migration pressure is frontend/tooling, but backend security updates are also urgent.

Recommended path: staged upgrade, not a direct Vue 2 to Vue 3 rewrite in one release.

1. First release: security and tooling stabilization with Laravel patch/minor updates, npm security review, Sass warning reduction where possible, and automated smoke tests for reader/import/vocabulary flows.
2. Second release: replace Laravel Mix/Webpack with Vite while keeping Vue 2 compatibility only if feasible after a spike.
3. Third release: Vue 3 + Vuetify 3 migration with reader/vocabulary/review components treated as manual rewrites.

## Audit Evidence

All command logs are stored in `upgrade-audit-output/logs/`.

| Area | Command Log | Result |
|---|---|---|
| Composer direct packages | `composer-docker-show-direct.txt` | Passed via `composer:2` Docker image |
| Composer outdated | `composer-docker-outdated-direct.txt` | Passed, updates available |
| Composer audit | `composer-docker-audit.txt` | Failed with advisories, action required |
| Local composer commands | `composer-show-direct.txt`, `composer-outdated-direct.txt`, `composer-audit.txt` | Local `composer` missing; Docker fallback used |
| NPM direct packages | `npm-ls-depth0.txt` | Passed |
| NPM outdated | `npm-outdated.txt` | Exited 1 because outdated packages exist |
| NPM audit | `npm-audit-omit-dev.txt` | Failed with vulnerabilities, action required |
| Production build | `npm-run-production.txt` | Passed, but emitted Sass deprecation warnings |
| Vue 2 pattern scan | `rg-vue2-patterns.txt` | Findings present |
| Vuetify pattern scan | `rg-vuetify-patterns.txt` | Findings present |
| Sass/build scan | `rg-sass-build-patterns.txt` | Findings present |
| Backend migration scan | `rg-backend-migration-patterns.txt` | Findings present |

## Current Stack Snapshot

Backend direct dependencies from Composer:

| Package | Installed | Notes |
|---|---:|---|
| `laravel/framework` | 11.15.0 | Multiple security advisories; update within Laravel 11 first |
| `laravel/horizon` | 5.25.0 | Outdated; Docker composer image lacks `ext-pcntl`, so latest detection is noisy |
| `laravel/reverb` | 1.0.0 | Critical/high advisories; update required |
| `laravel/sanctum` | 4.0.2 | Minor update available |
| `laravel/ui` | 4.5.2 | Minor update available |
| `league/csv` | 9.16.0 | Minor update available |
| `predis/predis` | 2.2.2 | Major update available |
| `pusher/pusher-php-server` | 7.2.4 | Patch update available |
| `phpunit/phpunit` | 11.2.7 | Security advisory; update within 11.x first |

Frontend direct dependencies from npm:

| Package | Installed | Latest | Risk |
|---|---:|---:|---|
| `vue` | 2.7.16 | 3.5.34 | High: framework migration |
| `vuetify` | 2.7.2 | 4.0.7 | High: component/API migration |
| `vuex` | 3.6.2 | 4.1.0 | Medium/High: state migration; Pinia should be evaluated |
| `vue-router` | 3.6.5 | 5.0.7 | Medium/High: router API changes |
| `laravel-mix` | 6.0.x | current path is Vite | High: build tool replacement |
| `bootstrap` | 4.6.2 | 5.3.8 | Medium: Sass and markup changes |
| `axios` | 0.21.4 | 1.16.1 | Medium: API compatibility review needed |
| `sass-loader` | 11.1.1 | 17.0.0 | Medium: tied to build tool version |
| `webpack` | 5.74.0 | 5.107.1 | Low/Medium if Mix remains temporarily |

## Security Findings

| Area | Finding | Evidence | Risk | Suggested Migration Path | Blocks Upgrade? |
|---|---|---|---|---|---|
| Backend | `composer audit` reports 25 advisories affecting 13 packages | `composer-docker-audit.txt` | High | Update Laravel 11, Reverb, Symfony transitive packages via `composer update` with constraints review | Yes |
| Backend | `laravel/reverb` 1.0.0 has critical/high advisories | `composer-docker-audit.txt` | High | Update Reverb to latest compatible 1.x before any frontend migration | Yes |
| Backend | `laravel/framework` 11.15.0 affected by multiple advisories | `composer-docker-audit.txt` | High | Update to current Laravel 11 patch/minor; do not jump to Laravel 13 in same release | Yes |
| Backend dev | `phpunit/phpunit` 11.2.7 has a high advisory | `composer-docker-audit.txt` | Medium | Update to safe 11.x or 12.x only after test compatibility check | No for runtime, yes for CI hygiene |
| Frontend | `npm audit --omit=dev` reports 6 vulnerabilities through Vue/showdown ecosystem | `npm-audit-omit-dev.txt` | High | Remove/replace vulnerable Vue 2-only packages as part of Vue 3 migration; review `vue-showdown` separately | Yes for long-term upgrade |
| Frontend | `showdown` ReDoS has no fix through current dependency path | `npm-audit-omit-dev.txt` | Medium | Evaluate replacing `vue-showdown` or sanitizing/limiting markdown input | Yes if user-controlled markdown is accepted |

## Deprecated And Legacy Pattern Inventory

| Area | Finding | Evidence | Risk | Suggested Migration Path | Blocks Upgrade? |
|---|---|---|---|---|---|
| App bootstrap | `app.js` uses `Vue.use`, global `Vue.component`, `new Vue`, `new VueRouter`, `new Vuex.Store` | `rg-vue2-patterns.txt` | High | Rewrite bootstrap to Vue 3 `createApp`, plugin registration, router 4, state replacement | Yes |
| Global components | Dozens of global `Vue.component(...)` registrations | `resources/js/app.js`, `rg-vue2-patterns.txt` | Medium | Convert to app-level registration or local imports during migration | Yes |
| Lifecycle | `beforeDestroy` used in reader, review, book chapters, dictionary import, text blocks | `rg-vue2-patterns.txt` | Medium | Rename/remap to Vue 3 `beforeUnmount`; verify Echo listener cleanup | Yes |
| `.sync` | `:options.sync` used in chapter/data tables | `TextReaderChapterList.vue`, `BookChapters.vue` | Medium | Convert to Vue 3 `v-model:*` or Vuetify 3 table model APIs | Yes |
| Filters | Vue 2 `filters` in vocabulary component | `resources/js/components/Vocabulary/Vocabulary.vue` | Medium | Replace with computed/method formatting | Yes |
| Store | Heavy direct `this.$store` and `mapState` usage | `rg-vue2-patterns.txt` | High | Either Vuex 4 bridge first or Pinia migration with module-by-module tests | Yes |
| Vuetify theme/breakpoint | `$vuetify.theme.currentTheme` and `$vuetify.breakpoint` heavily used | `rg-vuetify-patterns.txt` | High | Replace with Vuetify 3 theme/display composables or adapter layer | Yes |
| Vuetify components | Extensive `v-dialog`, `v-menu`, `v-data-table` usage | `rg-vuetify-patterns.txt` | High | Manual migration; data tables and dialogs need visual regression checks | Yes |
| Build | Laravel Mix/Webpack used in `package.json` scripts and `webpack.mix.js` | `package.json`, production build log | Medium/High | Spike Vite migration before Vue 3; if too costly, defer until Vue 3 branch | Yes for modern toolchain |
| Sass | `@import` used in app Sass; Bootstrap 4 emits many Dart Sass warnings | `npm-run-production.txt`, `rg-sass-build-patterns.txt` | Medium | Move app Sass to `@use`; Bootstrap 4 warnings require Bootstrap upgrade or Sass pinning | No immediate block, but blocks future Sass major |

## High-Risk Components

| Component/Area | Why It Is High Risk | Required Tests Before Migration |
|---|---|---|
| `resources/js/components/Text/TextBlockGroup.vue` | Central reader interaction: hover vocabulary, selection, SRS-related hover state, DOM geometry, store mutations | Reader open, word hover, phrase selection, vocabulary add/edit, SRS no-level-up-on-hover behavior |
| `resources/js/components/TextReader/TextReader.vue` | Reader lifecycle and keyboard/event cleanup | Open book, switch chapter, finish chapter, leave reader without stale listeners |
| `resources/js/components/TextReader/TextReaderChapterList.vue` | Uses Vuetify data table and `.sync`; chapter progress UX depends on it | Open chapter list, confirm read/unread markers, pagination/search |
| `resources/js/components/Library/BookChapters.vue` | Data table, Echo listener cleanup, chapter status updates | Import book, process chapters, verify statuses update live |
| `resources/js/components/Vocabulary/Vocabulary.vue` | Filters, dialogs, search, edit/export/import | Search, edit word, import/export, filter combinations |
| `resources/js/components/Review/Review.vue` | SRS behavior, timers, lifecycle cleanup | Start review, correct/wrong answer, finish review, leave page cleanup |
| Admin dictionary import components | Echo progress events and long-running imports | Start supported dictionary import, progress update, cancel/finish |

## Backend And Migration Safety Findings

| Area | Finding | Evidence | Risk | Suggested Migration Path | Blocks Upgrade? |
|---|---|---|---|---|---|
| Existing migrations | Many raw `DB::statement` operations and destructive column drops exist in historical migrations | `rg-backend-migration-patterns.txt` | Medium | Do not rewrite historical migrations unless necessary; test fresh install and upgrade path separately | No |
| Current custom migrations | New 2026 performance migrations add indexes and `unique_phrase_ids` column | `rg-backend-migration-patterns.txt` | Medium | Verify idempotency on existing DB and rollback behavior in staging | Yes before release |
| Dictionary import | Dynamic `Schema::create($databaseTableName)` and raw deletes in `DictionaryImportService.php` | `rg-backend-migration-patterns.txt` | Medium | Add integration test or manual checklist for dictionary import after Laravel updates | No, but high regression risk |
| Docker update safety | `docker-compose.yml` persists DB in `./database` and storage in `./storage` | `docker-compose.yml` | High | Document upgrade path: preserve volumes, do not recreate install dir with empty DB, run migrations after image update | Yes for release docs |
| Composer environment | Local `composer` missing; Docker fallback works but emits git safe.directory warning | local composer logs, Docker composer logs | Low | Use project PHP container or composer Docker image with safe.directory in CI/audit scripts | No |

## Test Gap Audit

Current test files:

| Test File | Coverage |
|---|---|
| `tests/Feature/Auth/AuthenticationTest.php` | Login/auth basics |
| `tests/Feature/Auth/RegistrationTest.php` | Registration basics |
| `tests/Feature/ExampleTest.php` | Framework example |
| `tests/Unit/ExampleTest.php` | Framework example |

Missing required pre-upgrade coverage:

| Flow | Current Coverage | Required Before Migration | Priority |
|---|---|---|---|
| Login/session timeout | Partial auth tests only | Feature test for login, session persistence, logout | High |
| Book import and chapter processing | Missing | Feature/integration test with small fixture and generated chapters | High |
| Large book reader opening | Missing | Performance smoke fixture that opens a book with many chapters without loading all text eagerly | High |
| Reader hover/select vocabulary | Missing | Browser/E2E or component test for hover state, selection, SRS guard behavior | High |
| Finish chapter/read marker | Missing | Feature + UI smoke for chapter read state | High |
| Vocabulary CRUD/edit/import/export | Missing | Feature tests for update endpoint and UI smoke for edit dialog | High |
| Review/SRS flow | Missing | Feature/E2E test for correct/wrong review and no accidental level-up from hover | High |
| Backup/restore | Missing | Docker/manual smoke test with DB backup import and login verification | High |
| Docker update with existing DB | Missing | Release checklist using persisted `database/` and `storage/` volumes | High |

## Build Audit Result

`npm run production` completed successfully. This means current assets can be built before migration work starts.

Warnings observed:

| Warning Group | Source | Meaning | Action |
|---|---|---|---|
| Dart Sass legacy JS API | Loader/tooling | Current Sass loader path uses deprecated JS API | Resolved by modern build stack/Vite or loader update |
| Sass `@import` | App Sass and Bootstrap import | `@import` removed in future Sass major | Convert app Sass to `@use`; Bootstrap 4 remains noisy |
| Sass `if()` / global builtins / `darken` / `lighten` / `abs` | Bootstrap 4 internals | Bootstrap 4 is incompatible with future Sass expectations | Upgrade Bootstrap or pin Sass until frontend migration |

## Recommended Migration Strategy Options

### Option A: Conservative Stabilization First

Scope:

- Update Composer packages within current major versions.
- Update high-risk runtime packages where possible without framework migration.
- Keep Vue 2/Vuetify 2 temporarily.
- Add missing smoke tests before touching the reader stack.

Pros: lowest release risk, fixes urgent backend security issues first.

Cons: Vue 2 vulnerabilities and deprecated frontend stack remain.

Use this as the next release path.

### Option B: Build Tool Migration Before Vue 3

Scope:

- Try Vite while keeping Vue 2 through compatibility plugins.
- Reduce Mix/Webpack/Sass technical debt.

Pros: smaller step than Vue 3; may improve dev/build performance.

Cons: Vue 2 + Vite bridge may be temporary work that is thrown away later.

Run as a spike only after Option A.

### Option C: Full Vue 3 + Vuetify 3 Migration

Scope:

- Vue 3 `createApp`, Vue Router 4, Vuetify 3, likely Pinia or Vuex 4 bridge.
- Manual rewrite of reader, vocabulary, review, dialogs, data tables, theme/breakpoint APIs.

Pros: resolves core frontend deprecation and security pressure.

Cons: high regression risk without E2E/browser coverage.

Do not start this until high-priority tests exist.

## Improvement Backlog

| ID | Improvement | Area | Risk Reduced | Suggested Order |
|---|---|---|---|---|
| I01 | Update Laravel 11/Reverb/Symfony transitive dependencies and rerun composer audit | Backend security | High | 1 |
| I02 | Add smoke tests for auth, reader open, vocabulary edit, chapter read marker, review flow | Tests | High | 2 |
| I03 | Add Docker update test/checklist with existing DB and storage volumes | Release safety | High | 3 |
| I04 | Replace or isolate `vue-showdown`/`showdown` usage | Frontend security | Medium | 4 |
| I05 | Create a Vue 3 migration branch and convert bootstrap only as a spike | Frontend architecture | High | 5 |
| I06 | Create Vuetify 2-to-3 component inventory for data tables/dialogs/menus | Frontend UI | High | 6 |
| I07 | Add adapter for theme/breakpoint access before Vuetify migration | Frontend UI | Medium | 7 |
| I08 | Convert app Sass imports from `@import` toward `@use` where not blocked by Bootstrap 4 | Build/Sass | Medium | 8 |
| I09 | Evaluate Bootstrap 5 removal/upgrade scope separately from Vuetify 3 | CSS/UI | Medium | 9 |
| I10 | Decide Vuex 4 bridge vs Pinia after mapping `vuex/` modules and reader store usage | State | High | 10 |

## Audit Completion Checklist

| Requirement From Plan | Evidence | Status |
|---|---|---|
| Dependency inventory collected | Composer Docker logs, npm logs | Complete |
| Composer audit run | `composer-docker-audit.txt` | Complete, advisories found |
| NPM audit run | `npm-audit-omit-dev.txt` | Complete, vulnerabilities found |
| Production build run | `npm-run-production.txt` | Complete, build passes with warnings |
| Vue 2/Vuetify 2 pattern scans run | `rg-vue2-patterns.txt`, `rg-vuetify-patterns.txt` | Complete |
| Sass/build scan run | `rg-sass-build-patterns.txt`, production warnings | Complete |
| Backend/migration safety scan run | `rg-backend-migration-patterns.txt` | Complete |
| Test gap audit completed | `tests/` inventory and gap table above | Complete |
| Report with findings/risk/options created | This document | Complete |

## Final Recommendation

Do not attempt a one-shot dependency update to latest versions. The current app has too many Vue 2/Vuetify 2 assumptions in reader, vocabulary, review, global bootstrap, theme/breakpoint handling, and data tables.

The next safe technical step is a stabilization release:

1. Patch backend Composer security issues first.
2. Add high-value smoke tests for reader/vocabulary/review/import/backup/update flows.
3. Then run a Vite/Vue 3 migration spike with the high-risk components isolated.
