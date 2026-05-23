# Actionable Audit Plan For LinguaCafe Upgrade Planning

This document defines the audit work needed before creating a concrete migration plan for deprecated calls, Vue 2, Vuetify 2, Laravel Mix, Sass, and related tooling.

## Summary

Goal: collect a concrete list of deprecated and legacy areas before deciding an upgrade path. The audit must not update dependencies or change application code. The expected output is `UPGRADE_AUDIT_REPORT.md` with findings, affected files, risk, blockers, and recommended migration options.

## Audit Areas

### 1. Dependency Inventory

Collect current versions and available upgrades:

```bash
cd /data/git/LinguaCafe
composer show --direct
composer outdated --direct
composer audit
npm ls --depth=0
npm outdated
npm audit --omit=dev
```

Classify findings:

- Backend: Laravel, PHP, Horizon, Reverb, Sanctum, PHPUnit.
- Frontend legacy: Vue 2, Vuetify 2, Vuex 3, Vue Router 3, Laravel Mix, Bootstrap 4, Sass.
- Build chain: Laravel Mix/Webpack versus Vite.

### 2. Frontend Migration Surface

Scan `resources/js` and classify Vue 2/Vuetify 2 patterns:

```bash
rg -n "new Vue|Vue\.use|Vue\.component|beforeDestroy|destroyed|\.sync|\$listeners|\$scopedSlots|\$set|\$delete|filters:|mapState|this\.\$store" resources/js
rg -n "\$vuetify|v-data-table|v-dialog|v-menu|currentTheme|breakpoint|:options\.sync" resources/js
```

Capture:

- App bootstrap usage: `new Vue`, `Vue.use`, global `Vue.component`.
- Lifecycle usage: `beforeDestroy`, `destroyed`.
- Vuex usage: `this.$store`, `mapState`, module structure.
- Router usage: `vue-router@3`, history mode, guards if present.
- Vuetify 2 APIs: `$vuetify.breakpoint`, `$vuetify.theme.currentTheme`, `v-dialog`, `v-menu`, `v-data-table`, `:options.sync`.
- High-risk components: reader, review, vocabulary, import flows, especially `TextBlockGroup.vue`.

Output:

- Component count by risk: Low, Medium, High.
- List of files requiring manual migration versus mechanical migration.

### 3. Sass And Build Deprecation Audit

Run production build and capture warnings:

```bash
npm run production
```

Inspect Sass and build tooling:

```bash
rg -n "@import|darken\(|lighten\(|map-merge|~bootstrap" resources/sass package.json
```

Capture:

- Sass `@import` usage across app Sass files.
- Bootstrap 4 Sass deprecation warnings.
- Laravel Mix config in `webpack.mix.js`.
- Whether Vite migration can happen before Vue 3 migration or should wait.

Output:

- Warning groups by source: app Sass, Bootstrap, loader/tooling.
- Recommended cleanup order.

### 4. Backend And Migration Safety Audit

Laravel is already modern, but migration safety still needs review.

Search:

```bash
rg -n "DB::statement|dropColumn|DROP COLUMN|rename\(|Schema::table|Schema::create" database app config routes
```

Capture:

- Deprecated Laravel APIs in controllers, services, migrations, config, routes.
- Risky migrations: raw SQL, destructive `DROP COLUMN`, table renames, data backfills.
- Docker PHP image compatibility with future Laravel/PHP updates.
- Existing DB update safety for Docker installs.

Output:

- Backend blockers table.
- Migration safety notes for existing installations.

### 5. Critical Flow Test Gap Audit

Inventory existing tests and identify missing smoke coverage before any upgrade.

Critical flows:

- Login/session.
- Book import and chapter processing.
- Opening reader.
- Hover/select vocabulary.
- Finish chapter and auto-level-up.
- Vocabulary search/edit/import/export.
- Review flow.
- Backup/restore.
- Docker update with existing DB.

Output:

- Current automated test inventory.
- Required pre-upgrade tests.
- Manual-only checks that should become automated if practical.

## Report Format

Create `UPGRADE_AUDIT_REPORT.md` after running the audit.

Required sections:

- Current Stack Snapshot.
- Dependency Upgrade Matrix.
- Deprecated/Legacy Pattern Inventory.
- High-Risk Components.
- Backend/Migration Safety Findings.
- Required Tests Before Upgrade.
- Recommended Migration Strategy Options.

Finding table schema:

```md
| Area | Finding | Evidence | Risk | Suggested Migration Path | Blocks Upgrade? |
|---|---|---|---|---|---|
```

Risk values:

- `High`: can break reader, import, auth, data persistence, or Docker updates.
- `Medium`: likely code changes, but bounded.
- `Low`: mostly mechanical/tooling cleanup.

## Audit Commands Checklist

```bash
cd /data/git/LinguaCafe

composer show --direct
composer outdated --direct
composer audit

npm ls --depth=0
npm outdated
npm audit --omit=dev
npm run production

rg -n "new Vue|Vue\.use|Vue\.component|beforeDestroy|destroyed|\.sync|\$listeners|\$scopedSlots|\$set|\$delete|filters:|mapState|this\.\$store" resources/js

rg -n "\$vuetify|v-data-table|v-dialog|v-menu|currentTheme|breakpoint|:options\.sync" resources/js

rg -n "@import|darken\(|lighten\(|map-merge|~bootstrap" resources/sass package.json

rg -n "DB::statement|dropColumn|DROP COLUMN|rename\(|Schema::table|Schema::create" database app config routes
```

## Assumptions

- Audit must not modify source files, lockfiles, migrations, or package versions.
- Main expected migration pressure is frontend, not Laravel backend.
- The audit produces concrete areas for a future upgrade plan, but does not perform the upgrade.
- Prefer staged migration unless the audit proves Vue 3/Vuetify 3 rewrite is unavoidable.
