# LinguaCafe Resources Overview (`resources/`)

This directory contains the entire frontend codebase including Vue modules, Vuex stores, SASS styling sheets, and Blade template files.

---

## 1. Directory Structure

### 📁 [js/](file:///c:/q/git/linguacafe/LinguaCafe/resources/js)
The core Vue 2 frontend codebase.
* **[app.js](file:///c:/q/git/linguacafe/LinguaCafe/resources/js/app.js)**: Configures global plugins (e.g. `vue-router`, `vue-cookie`, Showdown Markdown renderers), registers all Vue components, maps client-side routes, and initializes the Vue/Vuex store instance.
* **`components/`**: Modular subdirectories containing Vue files:
  * `TextReader/`: The main interactive reading page (`TextReader.vue`, layout configuration, glossaries).
  * `Review/`: Spaced repetition flashcard testing interfaces.
  * `Home/`: Dashboard statistics, calendar grids, and targets.
  * `Library/`: Book displays and subtitle/website importers.
  * `UserSettings/` & `Admin/`: Account settings, language and dictionary managers, and font files loaders.
* **`vuex/`**: Application state stores (`Shared.js`, `InteractiveText.js`, `HoverVocabularyBox.js`, `VocabularyBox.js`).
* **`services/`**: Communication interfaces mapping backend endpoints.

### 📁 [sass/](file:///c:/q/git/linguacafe/LinguaCafe/resources/sass)
Modular SASS directories corresponding to the component structure.
* [app.scss](file:///c:/q/git/linguacafe/LinguaCafe/resources/sass/app.scss): Main stylesheet bundle compiling all sub-SASS styles.
* [DarkMode.scss](file:///c:/q/git/linguacafe/LinguaCafe/resources/sass/DarkMode.scss): Core styling definitions for dark theme components.

### 📁 [views/](file:///c:/q/git/linguacafe/LinguaCafe/resources/views)
Standard Laravel Blade template engine files.
* **`layouts/`**:
  * [user.blade.php](file:///c:/q/git/linguacafe/LinguaCafe/resources/views/layouts/user.blade.php): The master HTML structure. Injects compiled styles (`mix('css/app.css')`), runtime script bundles (`mix('js/app.js')`), and sets up CSRF verification tags and dynamic fonts loading scripts.

### 📁 [vue3/](file:///c:/q/git/linguacafe/LinguaCafe/resources/vue3)
An experimental folder detailing the early planning and migration of the interface towards a **Vite + Vue 3 + Tailwind CSS + PrimeVue** architecture. It is not currently active in the core compilation scripts (`webpack.mix.js`).

---

## 2. Compilation
Development asset compiler configurations are defined in the workspace root in `webpack.mix.js`.
* **Recompile Assets (Development)**:
  ```bash
  npm run dev
  ```
* **Production Optimizations**:
  ```bash
  npm run prod
  ```
