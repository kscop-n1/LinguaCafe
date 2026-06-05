# Migration Audit

## Target stack

- Vue 3 only
- Vuetify 3 only
- Vue Router 4 only
- Vuex 4 only
- Vite only
- No Laravel Mix
- No Bootstrap 4
- No jQuery
- No Popper.js v1
- No Vue 2 compiler/runtime APIs

## Forbidden dependencies

- vue-template-compiler
- vue-loader v15
- laravel-mix
- bootstrap
- jquery
- popper.js
- vuetify v2
- vue-router v3
- vuex v3
- vue2-*
- @vue/compat (unless explicitly used as temporary migration layer)

## Forbidden source patterns

- new Vue(
- Vue.use(
- Vue.extend(
- Vue.component(
- Vue.directive(
- Vue.filter(
- Vue.set(
- Vue.delete(
- this.$set(
- this.$delete(
- this.$listeners
- this.$children
- this.$scopedSlots
- beforeDestroy
- destroyed
- filters:
- | filterName
- .native
- .sync
- slot-scope
- slot="
- <template functional
- functional: true
- /deep/
- >>>
- ::v-deep without Vue 3 syntax review
- vuetify/lib
- vuetify/es5
- v-list-item-content
- v-list-item-group
- v-app-bar-nav-icon legacy usage review
- v-icon legacy text syntax review
- v-model legacy component contract: value/input
- this.$destroy (removed Vue 2 instance destroy API)
- this.$on / this.$off / this.$once (removed Vue 2 instance event emitter APIs)
- new VueRouter (Vue Router v3 instantiation)
- new Vuex.Store (Vuex v3 instantiation)
- Vue.config.keyCodes / Vue.config.productionTip (removed Vue 2 global configs)
- v-content (Vuetify v2 main content tag, renamed to v-main)
- v-simple-table (Vuetify v2 simple table tag, renamed to v-table)


## Migration validation layers

Migration validation is split into three levels. Keep the levels separate so hard gates stay reliable while semantic migration residue still becomes visible.

### Level 1: hard fail checks

Run with:

```sh
npm run check:legacy:hard
```

Hard checks fail the command because these patterns are not valid in the Vue 3 / Vuetify 3 stack:

- Vue 2 global APIs: `new Vue`, `Vue.use`, `Vue.extend`, `Vue.component`, `Vue.directive`, `Vue.filter`, `Vue.set`, `Vue.delete`
- Removed Vue 2 instance APIs: `this.$set`, `this.$delete`, `this.$on`, `this.$off`, `this.$once`, `this.$destroy`
- Removed lifecycle hooks: `beforeDestroy`, `destroyed`
- Vue 2 filter syntax: `filters:`, template filter pipes
- Vue 2 slot and event modifiers: `.native`, `.sync`, `slot-scope`, `slot="..."`, functional templates
- Old deep selector syntax: `/deep/`, `>>>`, `::v-deep`
- Legacy imports and dependency residue in source: `vuetify/lib`, `vuetify/es5`
- Vue Router 3 / Vuex 3 constructors: `new VueRouter`, `new Vuex.Store`
- Removed Vue 2 config: `Vue.config.keyCodes`, `Vue.config.productionTip`
- Vue 2 custom v-model event: `$emit('input', ...)`
- Vuetify 2-only components: `v-list-item-content`, `v-list-item-icon`, `v-list-item-avatar`, `v-list-item-group`, `v-content`, `v-simple-table`
- Vuetify 2 item prop: `item-text`

### Level 2: migration review warnings

Run with:

```sh
npm run audit:migration:review
```

Review checks do not fail by default. They print candidates that need manual classification because the same syntax can be valid business data in one file and migration residue in another file.

Current review categories:

- `props` named `value`; check whether the component should use `modelValue` and emit `update:modelValue`
- `value:` fields; classify business objects separately from v-model or data-table residue
- `headers`, `text:`, and `value:` near each other; Vuetify 3 data-table headers should use `title` and `key`
- `@input` and `@change`; Vuetify 3 controls often need `v-model` or `@update:modelValue`
- `v-data-table`; review headers, item slots, row events, and prop contracts
- `v-pagination`; review `v-model` / `update:modelValue`
- `v-select`, `v-autocomplete`, `v-combobox`; review `item-title`, `item-value`, and model contracts
- `v-list-item` with direct icon/image/flag-like content; review `#prepend`
- old visual props: `dense`, `outlined`, `text`, `small`, `large`, `dark`, `light`
- `item-value`; valid in Vuetify 3, but the item contract may differ
- named `v-model:<prop>`; verify the child emits `update:<prop>`
- table row/pagination events; verify Vuetify 3 event signatures

### Level 3: regression-specific verification

Static checks are not enough for migration quality. For confirmed regressions, add or run targeted verification for the actual behavior:

- language modal loads and selects languages
- sidebar language flags/icons render in the expected list item slot
- vocabulary pagination changes page and reloads rows
- admin dictionary tables display rows with the expected headers
- browser console stays clean on the affected screen

## Known false positives

- `value:` is common in business data, language options, select items, and plain JavaScript objects. Only classify it as migration residue when it belongs to a custom v-model contract or a Vuetify 2 data-table header.
- `text:` can be a business field or label. It is only a Vuetify 3 table migration issue when it is part of a `v-data-table` header definition that should be `title`.
- `item-value` is still a Vuetify 3 prop. Review it when the item objects changed shape or when the paired display prop still uses `item-text` instead of `item-title`.
- `@change` can be valid on native inputs or custom events. Treat it as suspicious mainly on Vuetify model components.
- visual words like `text`, `small`, `large`, `dark`, and `light` can appear in CSS classes or domain text. Only migrate them when they are Vuetify 2 component props.

## How to classify review findings

For each warning from `npm run audit:migration:review`, classify it as one of:

- `hard migration bug`: behavior is broken or the code uses a removed Vue/Vuetify contract; fix it and consider promoting the pattern to `check:legacy:hard` if false positives are low.
- `reviewed false positive`: syntax is valid for business data, CSS, native DOM, or current Vuetify 3 API; leave it and document the reason if it is noisy.
- `behavior backlog`: the finding may affect product behavior but is not proven to be a migration regression; track it outside the hard migration gate.
- `needs runtime verification`: static code is ambiguous; verify with browser interaction, console output, API response, or a focused test.

## Commands before every migration commit

Run the strict gate:

```sh
npm run check:migration
```

Run the review report and classify any high-risk findings touched by the change:

```sh
npm run audit:migration:review
```

For frontend migration work, also run the CSS check when styles or Vuetify classes changed:

```sh
npm run check:css
```

Before release or image work, validate the affected screen in a browser and inspect the browser console.

## Migration review classification - 2026-06-05 regression pass

Audit snapshot saved during this pass:

```sh
/tmp/linguacafe-migration-review-audit-2026-06-05.txt
```

The report printed 163 candidates. They were classified by review category rather than mass-rewritten line by line.

### Confirmed regression root causes fixed

- Language dialog Vue 3 custom model contract: `resources/js/components/Dialogs/LanguageSelectionDialog.vue` now declares `modelValue`, emits `update:modelValue`, and watches `modelValue` before loading language data. The shared `dialogValue` helper in `resources/js/app.js` also prefers declared `modelValue`.
- Related dialog high-risk duplicates using `dialogValue`: known open-side-effect watchers now use `modelValue` in Language selection, Start review, Admin uninstall languages, and TextReader chapter list. Dialog components touched in this migration pass declare `modelValue` and `update:modelValue` instead of the old `value`/`input` contract.
- Language sidebar flag/icon rendering: `resources/js/components/Layout.vue` uses Vuetify 3 `#prepend` slots for sidebar icons and the selected-language flag, with `selectedLanguageFlagSource` guarding empty language values.
- Vocabulary pagination: `resources/js/components/Vocabulary/Vocabulary.vue` uses `v-model="currentPage"` with `@update:model-value="moveToPage"`; the legacy `@input` pagination handler is gone.
- Admin dictionary data table headers: `resources/js/components/Admin/AdminDictionarySettings.vue` uses Vuetify 3 `title`/`key` headers. Directly related admin table duplicates in Font and User settings also use `title`/`key`.

### High-risk migration residue reviewed and already migrated

- `headers-text-value` and `v-data-table` audit candidates in Admin Dictionary, Admin Font Type, Admin User, Book Chapters, Book List Table, TextReader, and TextReader Chapter List were checked. Their table headers use `title`/`key`; remaining audit output is from broad `:headers` or `headers:` detection, not confirmed legacy `text/value` table headers.
- `v-pagination` audit candidate in Vocabulary was checked and is already on Vuetify 3 `@update:model-value`.
- `list-item-direct-prepend-content` candidates in `Layout.vue` were checked and are false positives after the migration because the block contains `#prepend` slot content. Vocabulary sort-menu items use `prepend-icon`, which is valid Vuetify 3 usage.
- `named-v-model` candidates for `v-model:options` on data tables were reviewed as valid Vuetify 3 server/table option binding, not part of the reported regressions.

### Reviewed false positives / valid Vue 3 or Vuetify 3 usage

- `options-value-field` candidates in Review settings, TextReader settings, Theme settings, and hover vocabulary store commits are business option values or Vuex payload object fields. They are not custom v-model props and should remain review-only.
- `vuetify-selection-controls` candidates are broad component-presence warnings. The confirmed form-control migration class is covered by hard checks for legacy `@input`/`@change` model events; these remaining entries need classification only when a touched select/autocomplete/combobox has broken behavior.
- `item-value` candidates are valid Vuetify 3 props. They remain review warnings because item object contracts can still drift, but no regression from this pass proves they are wrong.
- `old-visual-props` candidates such as `v-icon large`, `v-icon small`, and `v-chip small` remain review warnings. They are not part of the confirmed language modal, sidebar flag, pagination, or admin dictionary table regressions.

### Product logic backlog, not migration

- Non-word tokens such as dice/math tokens remain product parsing logic backlog. The regression plan did not prove this is caused by Vue 3 or Vuetify 3 migration.
- Vocabulary book filtering after the unique-word-id change remains backend/data-model backlog and needs a separate root-cause pass.
- Admin backup 401 and mobile Firefox-only API/vocabulary layout reports still need runtime/browser reproduction before being classified as migration fixes.

### Commands run for this classification pass

```sh
npm run audit:migration:review
npm run check:migration
```
