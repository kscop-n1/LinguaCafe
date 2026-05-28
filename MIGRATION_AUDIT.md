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

