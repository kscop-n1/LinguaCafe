# LinguaCafe Storage Layer Overview (`storage/`)

This directory is used by Laravel to store runtime application files, session files, cached layouts, error logs, and user-uploaded media assets.

---

## 1. Directory Structure

### 📁 [app/](file:///c:/q/git/linguacafe/LinguaCafe/storage/app)
Contains files generated or uploaded during application usage.
* **`dictionaries/`**: Stores temporary dictionary files, raw CC-CEDICT, JMDict, or csv vocabulary sheets uploaded during dictionary import workflows.
* **`public/`**: Stores user-uploaded media files that are publicly accessible.
  * **`book_images/`**: Covers for user-imported books.
  * **`fonts/`**: Custom fonts loaded for rendering specific character sets (e.g. Cyrillic, Thai, Chinese/Japanese).

### 📁 `framework/`
Managed entirely by the Laravel framework (mostly ignored in version control).
* **`cache/`**: Data caches.
* **`sessions/`**: Session state database files when utilizing the `file` session driver.
* **`views/`**: Compiled PHP files compiled from Laravel Blade templates.

### 📁 `logs/`
* **`laravel.log`**: Contains the central application debug logs, stack traces, and database/external query error logs. Critical first-stop location for troubleshooting backend bugs.
