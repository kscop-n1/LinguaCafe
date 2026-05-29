Я глянув на `package.json`: в оригіналі LinguaCafe був Laravel Mix + Vue 2 + Vuetify 2 + vue-router 3 + vuex 3 + Bootstrap/jQuery/Popper, а у твоєму fork вже стоїть Vite + Vue 3 + Vuetify 3 + vue-router 4 + vuex 4. Тобто залежності вже сильно посунуті вперед, але це **не гарантує**, що код не містить Vue 2/Vuetify 2/CSS legacy-патернів. ([GitHub][1])

## Головна помилка процесу

Ти, ймовірно, просиш Codex:

> “перевір, що Vue 2 більше не застосовується”

А треба змусити його працювати так:

> “створи повний машинно-перевірний список заборонених legacy-патернів, додай CI/скрипти, які падають при їх появі, створи міграційний звіт, і тільки потім фікси код до зеленого статусу”.

Інакше Codex кожного разу буде “знаходити ще щось”, бо немає **закритого списку критеріїв завершення**.

Vue офіційно має довгий список breaking changes: `new Vue() -> createApp`, `Vue.use`, `Vue.set/delete`, `$listeners`, `$children`, filters, `.native`, `.sync`, `beforeDestroy -> beforeUnmount`, `destroyed -> unmounted`, зміни transition-класів, router/vuex upgrades тощо. Тобто “прибрати Vue 2” — це не один пошук по `vue@2`, а десятки патернів. ([v3-migration.vuejs.org][2])

---

# Що зробити вже сьогодні

## 1. Створи файл `MIGRATION_AUDIT.md`

Не проси Codex одразу фіксити. Перший goal має бути **тільки інвентаризація**.

Файл має містити:

```md
# Migration Audit

## Target stack

- Vue 3 only
- Vuetify 3 only
- Vue Router 4 only
- Vuex 4 only, або Pinia якщо буде окреме рішення
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
- @vue/compat, unless explicitly used as temporary migration layer

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
```

Це не ідеальний список, але він вже різко зменшує хаос.

---

## 2. Додай скрипт, який падає при legacy-патернах

Створи файл:

```bash
mkdir -p scripts
nano scripts/check-legacy.sh
```

Встав:

```bash
#!/usr/bin/env bash
set -euo pipefail

echo "Checking for legacy Vue 2 / Vuetify 2 / Laravel Mix patterns..."

FAILED=0

check() {
  local pattern="$1"
  local description="$2"

  if rg -n --hidden --glob '!node_modules' --glob '!vendor' --glob '!storage' --glob '!public/build' --glob '!package-lock.json' "$pattern" .; then
    echo ""
    echo "❌ Found legacy pattern: $description"
    echo "Pattern: $pattern"
    echo ""
    FAILED=1
  fi
}

# Dependencies / build tooling
check '"vue-template-compiler"' "Vue 2 compiler dependency"
check '"laravel-mix"' "Laravel Mix dependency"
check '"vue-loader": ?"(\^|~)?15' "Vue 2 vue-loader v15"
check '"bootstrap": ?"(\^|~)?4' "Bootstrap 4 dependency"
check '"jquery"' "jQuery dependency"
check '"popper.js"' "Popper.js v1 dependency"
check '"vue2-' "Vue 2-specific package"

# Vue 2 global API
check 'new Vue\s*\(' "Vue 2 app initialization"
check 'Vue\.use\s*\(' "Vue 2 plugin installation"
check 'Vue\.extend\s*\(' "Vue.extend API"
check 'Vue\.component\s*\(' "Global Vue.component usage"
check 'Vue\.directive\s*\(' "Global Vue.directive usage"
check 'Vue\.filter\s*\(' "Vue filters"
check 'Vue\.set\s*\(' "Vue.set"
check 'Vue\.delete\s*\(' "Vue.delete"

# Vue 2 instance API
check 'this\.\$set\s*\(' "this.$set"
check 'this\.\$delete\s*\(' "this.$delete"
check 'this\.\$listeners' "this.$listeners"
check 'this\.\$children' "this.$children"
check 'this\.\$scopedSlots' "this.$scopedSlots"
check '\$destroy\s*\(' "Vue 2 $destroy"

# Lifecycle
check '\bbeforeDestroy\b' "beforeDestroy lifecycle hook"
check '\bdestroyed\b' "destroyed lifecycle hook"

# Template syntax
check '\.native\b' "v-on.native modifier"
check '\.sync\b' ".sync modifier"
check 'slot-scope=' "Old slot-scope syntax"
check 'slot=' "Potential old named slot syntax"
check '<template functional' "Vue 2 functional SFC"
check '\bfunctional:\s*true\b' "Vue 2 functional component option"

# Filters
check '\bfilters\s*:' "Vue 2 filters option"
check '\{\{[^}]*\|[^}]*\}\}' "Vue 2 template filter pipe"

# Old deep selectors
check '/deep/' "Old deep selector"
check '>>>' "Old deep selector"

# Vuetify 2 import style / components
check 'vuetify/lib' "Vuetify 2 import path"
check 'vuetify/es5' "Vuetify 2 import path"
check '<v-list-item-content' "Vuetify 2 component removed/changed in Vuetify 3"
check '<v-list-item-group' "Vuetify 2 component removed/changed in Vuetify 3"

if [ "$FAILED" -eq 1 ]; then
  echo "Legacy check failed."
  exit 1
fi

echo "✅ No known legacy patterns found."
```

Потім:

```bash
chmod +x scripts/check-legacy.sh
```

У `package.json` додай:

```json
{
  "scripts": {
    "check:legacy": "bash scripts/check-legacy.sh"
  }
}
```

І запускай:

```bash
npm run check:legacy
```

Оце має стати твоїм **головним стоп-краном**. Не “Codex каже, що все ок”, а “скрипт не знаходить legacy”.

---

## 3. Дай Codex не “пофікси все”, а “доведи скрипт до зеленого”

Ось нормальний goal для Codex CLI.

```text
Goal: Complete the Vue 2 / Vuetify 2 / legacy frontend cleanup using a machine-verifiable audit.

Context:
This repository is a fork of LinguaCafe migrated from Laravel Mix + Vue 2 + Vuetify 2 to Vite + Vue 3 + Vuetify 3. Previous iterations fixed legacy code in small fragments, but new Vue 2 / Vuetify 2 / legacy patterns keep appearing. I want to stop this by creating a repeatable audit and cleanup process.

Tasks:
1. Create or update scripts/check-legacy.sh.
2. The script must scan the repository with ripgrep and fail if it finds known legacy patterns.
3. Add npm script "check:legacy" to package.json.
4. Run npm run check:legacy.
5. For every finding, fix the source code properly for Vue 3 / Vuetify 3 / Vite.
6. Do not suppress findings unless the pattern is a false positive.
7. If something is a false positive, document it explicitly in MIGRATION_AUDIT.md and narrow the regex or exclude only the specific generated/vendor path.
8. Do not modify node_modules, vendor, storage, public/build, or generated assets.
9. After fixes, run:
   - npm run check:legacy
   - npm run production
10. Update MIGRATION_AUDIT.md with:
   - what was checked
   - what was fixed
   - remaining risks
   - exact commands that now pass

Definition of done:
- npm run check:legacy passes.
- npm run production passes.
- package.json does not contain Vue 2, Vuetify 2, Laravel Mix, Bootstrap 4, jQuery, Popper.js v1, or vue-template-compiler.
- No Vue 2 global APIs remain.
- No Vue 2 lifecycle hooks remain.
- No Vue 2 filters remain.
- No obvious Vuetify 2-only components/import paths remain.
- Any remaining warning is documented as either external dependency noise, generated asset noise, or a separate modernization task.
```

А коротка версія для консолі:

```text
Create a machine-verifiable frontend legacy audit. Add scripts/check-legacy.sh + npm run check:legacy to detect Vue 2, Vuetify 2, Laravel Mix, Bootstrap 4, jQuery, old slot/filter/lifecycle/deep-selector patterns. Fix all findings properly for Vue 3/Vuetify 3/Vite, document false positives in MIGRATION_AUDIT.md, then run npm run check:legacy and npm run production until both pass.
```

---

## 4. Не змішуй “міграцію Vue” і “модернізацію CSS” в одну задачу

Тут важливий момент: **Vue 2 cleanup, Vuetify 3 cleanup, Bootstrap removal, Sass modernization, CSS theme bugs — це різні треки**.

Інакше Codex буде одночасно лагодити `beforeDestroy`, білий текст на білому фоні, Bootstrap Sass warnings, Vite asset path, і ще випадково перепише половину UI.

Я б розбив так:

### Етап A — Dependency cleanup

Мета: `package.json`, lockfile, Vite config, Laravel integration.

Команди:

```bash
npm ls vue vue-router vuex vuetify @vue/compiler-sfc vue-template-compiler laravel-mix bootstrap jquery popper.js
npm outdated
npm audit
npm run production
```

### Етап B — Vue 2 source cleanup

Мета: прибрати Vue 2 API, hooks, template syntax.

Команда:

```bash
npm run check:legacy
```

### Етап C — Vuetify 2 → Vuetify 3 cleanup

Окрема перевірка компонентів. Vuetify 3 має окремий upgrade path, і там багато змін не ловляться простим `npm ls`. ([Vuetify][3])

Патерни для перевірки:

```bash
rg -n --glob '!node_modules' --glob '!vendor' \
'v-list-item-content|v-list-item-group|v-simple-table|v-data-table|v-icon|v-chip|v-toolbar|v-app-bar|v-navigation-drawer|vuetify/lib|vuetify/es5' resources app
```

Тут не все автоматично “погано”, але все треба переглянути.

### Етап D — Bootstrap/jQuery/CSS removal

Якщо Bootstrap ще десь використовується в blade/css/js — не проси Codex “прибери Bootstrap”. Проси:

```text
Inventory every Bootstrap/jQuery usage first. Do not remove anything yet. Classify each usage as:
1. layout utility,
2. component behavior,
3. form styling,
4. modal/dropdown behavior,
5. dead code.
Then propose replacement strategy using Vuetify 3 or local CSS.
```

### Етап E — Visual regression

Тут потрібна не “логіка”, а список екранів.

Створи `VISUAL_TEST_PLAN.md`:

```md
# Visual Test Plan

## Must check after each frontend migration batch

- Login screen
- Register screen
- Reader page
- Text import page
- Vocabulary/sidebar
- Review page
- Admin/settings page
- User manual page
- Mobile bottom menu
- Dark mode
- Light mode
- Empty state screens
- Error screens

## Viewports

- 390x844 mobile
- 768x1024 tablet
- 1440x900 desktop
```

І давай Codex конкретну задачу: “перевір ці екрани вручну/через browser tooling, не просто build”.

---

## 5. Додай окремі “заборонені залежності” через npm script

Скрипт `check-legacy.sh` ловить текст у файлах, але залежності краще перевіряти окремо.

Створи:

```bash
nano scripts/check-deps.js
```

```js
const fs = require('fs');

const pkg = JSON.parse(fs.readFileSync('package.json', 'utf8'));

const allDeps = {
  ...(pkg.dependencies || {}),
  ...(pkg.devDependencies || {}),
};

const forbidden = [
  'vue-template-compiler',
  'laravel-mix',
  'bootstrap',
  'jquery',
  'popper.js',
  'vue2-circle-progress',
];

const forbiddenVersionRules = [
  ['vue', /^(\^|~)?2\./],
  ['vue-router', /^(\^|~)?3\./],
  ['vuex', /^(\^|~)?3\./],
  ['vuetify', /^(\^|~)?2\./],
  ['vue-loader', /^(\^|~)?15\./],
];

let failed = false;

for (const name of forbidden) {
  if (allDeps[name]) {
    console.error(`❌ Forbidden dependency found: ${name}@${allDeps[name]}`);
    failed = true;
  }
}

for (const [name, rule] of forbiddenVersionRules) {
  if (allDeps[name] && rule.test(allDeps[name])) {
    console.error(`❌ Forbidden legacy version found: ${name}@${allDeps[name]}`);
    failed = true;
  }
}

if (failed) {
  process.exit(1);
}

console.log('✅ Dependency legacy check passed.');
```

У `package.json`:

```json
{
  "scripts": {
    "check:deps": "node scripts/check-deps.js",
    "check:legacy": "bash scripts/check-legacy.sh",
    "check:migration": "npm run check:deps && npm run check:legacy && npm run production"
  }
}
```

Тепер твоя команда перед кожним commit:

```bash
npm run check:migration
```

---

## 6. Перестань приймати PR/commit від Codex без “diff review checklist”

Після кожного Codex-run проси не “що зробив?”, а:

```text
Before finishing, provide a migration review report:

1. Files changed.
2. Legacy patterns removed.
3. Legacy patterns still detected by npm run check:legacy.
4. Build result.
5. Any warnings that remain.
6. Whether warnings are from app code or external dependencies.
7. Any risky UI behavior that needs manual verification.
```

Це дуже важливо. Codex часто каже “done”, але не завжди розділяє:

* build warning,
* runtime warning,
* Sass deprecation,
* Vite asset warning,
* actual broken UI,
* dependency warning.

Тобі треба змусити його класифікувати кожен сигнал.

---

## 7. Для CSS/Sass зроби окремий “legacy CSS audit”

Бо Sass warnings від Bootstrap і global `@import` — це інший клас проблеми. Dart Sass давно рухає екосистему від `@import` до module system `@use` / `@forward`, і якщо у тебе ще Bootstrap 4 Sass або старі глобальні імпорти, Codex буде постійно латати симптоми. Laravel офіційно використовує Vite як asset bundler у сучасному стеку, тобто логічно тримати фронтенд-asset pipeline навколо Vite, а не Laravel Mix. ([Laravel][4])

Створи окремий скрипт:

```bash
nano scripts/check-css-legacy.sh
```

```bash
#!/usr/bin/env bash
set -euo pipefail

FAILED=0

check() {
  local pattern="$1"
  local description="$2"

  if rg -n --hidden --glob '!node_modules' --glob '!vendor' --glob '!storage' --glob '!public/build' "$pattern" resources; then
    echo ""
    echo "❌ Found legacy CSS/Sass pattern: $description"
    echo "Pattern: $pattern"
    echo ""
    FAILED=1
  fi
}

check '@import' "Sass @import usage; consider @use/@forward or plain CSS import strategy"
check 'bootstrap' "Bootstrap CSS/Sass usage"
check 'jquery' "jQuery-coupled styling or scripts"
check '/deep/' "Old deep selector"
check '>>>' "Old deep selector"
check '!important' "Potential CSS override debt; review manually"

if [ "$FAILED" -eq 1 ]; then
  echo "CSS legacy check failed."
  exit 1
fi

echo "✅ No known CSS legacy patterns found."
```

Але тут я б **не одразу додавав у `check:migration`**, бо `!important` і `@import` можуть бути тимчасово допустимими. Спочатку нехай Codex зробить звіт.

---

# Як я б переформатував твій весь процес

## Було

1. Codex, мігруй Vue 2 на Vue 3.
2. Codex щось фіксить.
3. Build проходить або майже проходить.
4. Ти бачиш нову проблему.
5. Знову Codex.
6. Знову нові залишки.

## Має бути

1. Зафіксувати target stack.
2. Зафіксувати forbidden stack.
3. Додати автоматичні checks.
4. Змусити Codex зробити inventory.
5. Змусити Codex фіксити тільки до проходження checks.
6. Додати visual test plan.
7. Приймати зміни тільки якщо:

   * `npm run check:migration` проходить;
   * список залишкових warning класифікований;
   * ручні екрани перевірені.

---

# Чому Codex зараз фіксить “маленькими шматочками”

Бо LLM не має природного поняття “повністю очищена кодова база”. Для нього “перевір Vue 2” може означати:

* подивитися `package.json`;
* пошукати `vue-template-compiler`;
* пошукати `new Vue`;
* подивитися один-два entry файли;
* пройтись по очевидних `.vue`;
* проігнорувати CSS, blade, markdown/manual, generated assets, старі imports, lockfile, tests.

А для тебе “повністю” означає:

* залежності;
* imports;
* runtime APIs;
* template syntax;
* lifecycle hooks;
* Vuetify components;
* Sass/CSS;
* build pipeline;
* generated assets;
* visual behavior;
* mobile/dark mode;
* user manual.

Це різні визначення “done”. Тому треба не “кращий prompt”, а **перевірочна система**.

---

# Мінімальний план на сьогодні

Я б зробив так:

1. Додати `scripts/check-deps.js`.
2. Додати `scripts/check-legacy.sh`.
3. Додати `npm run check:migration`.
4. Запустити локально:

```bash
npm run check:migration
```

5. Усе, що впало, віддати Codex одним goal:

```text
Run npm run check:migration, fix every failure properly, and do not stop until the command passes. Do not suppress checks unless documented as a false positive in MIGRATION_AUDIT.md.
```

6. Після цього окремо запускати треки:

   * Vuetify 3 component cleanup;
   * CSS/Sass cleanup;
   * visual bugs;
   * dependency modernization.

---

Моя жорстка рекомендація: **не давай Codex нових UI-багів, поки не поставиш check scripts**. Інакше ти просто продовжиш той самий цикл: він щось лагодить, паралельно залишає старі патерни, потім знаходить їх наступного разу і виглядає так, ніби міграція ніколи не закінчується.

[1]: https://raw.githubusercontent.com/simjanos-dev/LinguaCafe/main/package.json "raw.githubusercontent.com"
[2]: https://v3-migration.vuejs.org/migration-build.html "Migration Build | Vue 3 Migration Guide"
[3]: https://vuetifyjs.com/en/getting-started/upgrade-guide/ "Upgrade guide — Vuetify"
[4]: https://laravel.com/docs/11.x/vite "Asset Bundling (Vite) | Laravel 11.x - The clean stack for Artisans and agents"
