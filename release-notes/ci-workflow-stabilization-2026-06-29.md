# CI Workflow Stabilization - 2026-06-29

Source baseline:

- `release-notes/migration-release-candidate-checkpoint-2026-06-29.md`
- `release-notes/regression-reconciliation-tracker-2026-06-17.md`
- `release-notes/regression-stabilization-summary-2026-06-20.md`

## Scope

This update stabilizes GitHub Actions and test-command documentation for the current migration protected baseline. It does not start a UI regression batch, run cleanup apply, run metadata backfill apply, run phrase repair apply, or mutate production data.

## Audit Findings

Current GitHub workflow inventory before this change:

- `.github/workflows/build-and-push.yml`: release/manual Docker image publish workflow.
- `.github/workflows/test-image.yml`: manual Docker image publish workflow for test tags/images.
- `.github/workflows/beta-image.yml`: manual beta Docker image publish workflow.

Recent GitHub Actions state checked with `gh run list --limit 10`: the visible runs were successful Docker image publish runs. No current workflow was running the protected migration baseline.

Old failing or stale CI commands:

| Area | Previous command or state | Result | Root cause |
| --- | --- | --- | --- |
| Protected test CI | No GitHub Actions job existed for PHPUnit, frontend mounted tests, migration/static checks, CSS audit, dark-theme guard, production build, or whitespace checks | Required baseline was not enforced by CI | Stale/missing workflow coverage |
| Backend test documentation | `php artisan test` in `tests/README.md` | Stale command for this migration baseline | Current protected command is `./vendor/bin/phpunit` |
| Frontend mounted tests | No Actions step | Missing from CI | New Vitest/jsdom mounted tests were added after the old workflow surface |
| Migration/static checks | No Actions step | Missing from CI | Current contract is `npm run check:migration` |
| CSS and dark-theme checks | No Actions step | Missing from CI | REG-008 dark-theme work added/expanded guardrails |
| Production build | Only Docker image builds ran `npm run prod` inside image build; no standalone baseline test job enforced it | Build was coupled to publish workflows | Current baseline expects production build through `npm run check:migration` |

No real production application bug was found during the workflow audit.

## First CI Failure Follow-Up - 2026-06-30

First `Protected Baseline CI` failure:

- Failing command: `./vendor/bin/phpunit`.
- Failing test: `Tests\Feature\ExampleTest::test_the_application_returns_a_successful_response`.
- Failure: expected HTTP `200`, received `500`.
- Root exception: `Illuminate\Foundation\ViteManifestNotFoundException: Vite manifest not found at public/build/manifest.json`.

Root cause: the clean GitHub Actions checkout had no `public/build/manifest.json`, but the workflow ran PHPUnit before any production frontend build. `ExampleTest` requests `/login`, which renders `resources/views/layouts/user.blade.php`; that Blade layout calls `@vite(['resources/js/app.js'])` and therefore requires the manifest unless Vite is explicitly bypassed in the test.

Chosen fix: keep the HTTP feature test behavior intact and update workflow order so `npm run production` creates `public/build/manifest.json` before PHPUnit. The workflow then asserts the manifest exists with `test -s public/build/manifest.json`.

Why this does not weaken the baseline: no tests were removed, no Laravel `withoutVite()` bypass was added, and the protected `npm run check:migration` step still runs later. The workflow intentionally performs an early production build as a PHPUnit prerequisite and then runs the official migration/static/build contract again.

## Changed Files

- `.github/workflows/ci.yml`: new protected baseline workflow.
- `tests/README.md`: backend/frontend test commands updated to match the protected baseline.
- `release-notes/migration-release-candidate-checkpoint-2026-06-29.md`: RC readiness now explicitly depends on the updated GitHub Actions workflow passing after push.
- `release-notes/ci-workflow-stabilization-2026-06-29.md`: this stabilization record.

## Final CI Command List

The new `Protected Baseline CI` workflow runs on pull requests, pushes to `main`, and manual dispatch.

Environment assumptions:

- PHP: `8.2`, matching `composer.json` and the PHP Dockerfile runtime.
- Node: `20.19`, satisfying Vite/Vitest engine requirements from `package-lock.json`.
- Database: MySQL `8.0` service, matching the app default MySQL configuration and local dev database family.
- PHP extensions: `dom`, `fileinfo`, `gd`, `mbstring`, `mysqli`, `pdo_mysql`, `pcntl`, `zip`.
- Frontend test environment: Vitest with jsdom from `vitest.config.mjs`.

Commands:

1. `composer install --no-interaction --prefer-dist --no-progress`
2. `npm ci`
3. prepare Laravel runtime directories
4. `npm run production`
5. `test -s public/build/manifest.json`
6. `./vendor/bin/phpunit`
7. `npm run test:frontend`
8. `npm run check:migration`
9. `npm run check:css`
10. `node scripts/check-dark-theme-contrast.js`
11. `git diff --check`

Production build is now run once before PHPUnit to satisfy Blade/Vite HTTP tests in a clean checkout. It also remains covered by `npm run check:migration`, whose current contract is `npm run check:deps && npm run check:legacy:hard && npm run production`.

## Local Verification Results

Verified locally on 2026-06-29:

- PHP dependency install: `composer install --no-interaction --prefer-dist --no-progress` passed in the PHP 8.2 dev webserver container.
- Node dependency install: `npm ci` passed with no tracked `package.json` or `package-lock.json` changes.
- Full PHPUnit: `96 tests, 1090 assertions`.
- Frontend mounted tests: `1` file, `2` tests.
- `npm run check:migration`: passed.
- Production build: passed through `npm run check:migration`; Vite transformed `749` modules.
- `npm run check:css`: passed with `0` errors and `386` warning-only legacy CSS debt entries.
- `node scripts/check-dark-theme-contrast.js`: passed.
- `git diff --check`: passed.
- Tracked dependency/cache artifacts: `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `bootstrap/cache/packages.php`, and `bootstrap/cache/services.php` were unchanged by verification. The generated `public/build/manifest.json` was restored byte-for-byte after the missing-manifest simulation.

The local host does not have direct `php` or `composer` binaries, so PHP install and PHPUnit verification were run through the existing PHP 8.2 dev container. GitHub Actions cannot be executed fully from this local checkout. The exact workflow to validate after push is `Protected Baseline CI`, job `PHP, frontend, migration, CSS`.

Additional local verification on 2026-06-30 for the missing-manifest failure:

- Temporarily moved `public/build/manifest.json` out of the way to simulate a clean checkout without a Vite manifest.
- `npm run production`: passed and recreated `public/build/manifest.json`.
- `test -s public/build/manifest.json`: passed before PHPUnit.
- Full PHPUnit after manifest creation: `96 tests, 1090 assertions`.
- `npm run test:frontend`: passed, `1` file and `2` tests.
- `npm run check:migration`: passed and retained the protected production-build contract.
- `npm run check:css`: passed with `0` errors and `386` warning-only legacy CSS debt entries.
- `node scripts/check-dark-theme-contrast.js`: passed.
- `git diff --check`: passed.

## Remaining CI Risks

- The first GitHub Actions run should confirm that hosted `ubuntu-latest` with MySQL `8.0`, PHP `8.2`, and Node `20.19` matches the local Docker-backed baseline.
- If GitHub-hosted MySQL readiness differs from local Docker Compose, adjust only workflow service health or environment wiring unless the run exposes a real app bug.
- Existing Docker publish workflows remain separate from protected baseline CI.

## Production Impact

Production code did not change. Production data was not touched. No cleanup, metadata backfill, phrase repair, or production maintenance apply was run.
