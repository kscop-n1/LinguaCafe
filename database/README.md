# LinguaCafe Database Layer Overview (`database/`)

This directory contains the database lifecycle management tools including migrations, factories, and initial seeders.

---

## 1. Directory Structure

### 📁 [migrations/](file:///c:/q/git/linguacafe/LinguaCafe/database/migrations)
Contains PHP scripts defining database schema changes. The project has evolution migrations dating from 2021 up through 2025. Key database tables defined here include:
* **`users`**: Manages credentials, current selected language, settings flags, and user type (admin).
* **`books` & `chapters`**: Store imported libraries, raw text data, compressed token structure JSONs, processing statuses, and reading stats.
* **`encountered_words`**: Tracks learning stages ($stage \ge 0$ for known/new/skipped, $stage < 0$ for SRS intervals) and translation lookup data.
* **`phrases`**: Stores multi-word phrases created dynamically during reading.
* **`goals` & `goal_achievements`**: Log daily reading targets (e.g. read count target vs actual progress).
* **`dictionaries`**: Keeps track of local dictionary files and API services.
* **`deepl_caches`**: Stores translation responses for vocab words to reduce external API lookup requests.
* **`settings`**: Key-value system options (such as default SRS intervals).
* **`font_types`**: Stores references to customized language fonts uploaded by admins.

### 📁 [seeders/](file:///c:/q/git/linguacafe/LinguaCafe/database/seeders)
Initializes database tables with default values. [DatabaseSeeder.php](file:///c:/q/git/linguacafe/LinguaCafe/database/seeders/DatabaseSeeder.php) calls:
* [SettingsSeeder.php](file:///c:/q/git/linguacafe/LinguaCafe/database/seeders/SettingsSeeder.php): Populates primary settings like `reviewIntervals` (spaced repetition intervals), `languageSettings`, and theme maps.
* [DictionarySeeder.php](file:///c:/q/git/linguacafe/LinguaCafe/database/seeders/DictionarySeeder.php): Declares supported dictionary mappings.
* [FontTypeSeeder.php](file:///c:/q/git/linguacafe/LinguaCafe/database/seeders/FontTypeSeeder.php): Populates font type configurations.

### 📁 [factories/](file:///c:/q/git/linguacafe/LinguaCafe/database/factories)
* [UserFactory.php](file:///c:/q/git/linguacafe/LinguaCafe/database/factories/UserFactory.php): Model factory used during tests to quickly generate dummy users.

---

## 2. CLI Database Commands

* **Run Migrations**:
  ```bash
  php artisan migrate
  ```
* **Seed Default Data**:
  ```bash
  php artisan db:seed
  ```
* **Refresh Database (Destructive)**:
  ```bash
  php artisan migrate:fresh --seed
  ```
