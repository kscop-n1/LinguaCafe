# LinguaCafe Codebase Entrypoint & Overview

This document serves as the primary entry point and developer guide for exploring, investigating, and troubleshooting the LinguaCafe codebase. It describes the application architecture, directory structure, core services, database schemas, API integrations, and the test suite.

---

## 1. High-Level Architecture
LinguaCafe is a self-hosted language learning application consisting of three main parts:
1. **Laravel Backend (PHP 8.2+)**: Serves web/API routes, manages the database, queues background jobs, interacts with external translation services, and handles asset compilation.
2. **Python Tokenizer Service (spaCy/Python 3.10)**: An external container running a Python microservice (listening on port `8678`) that handles text tokenization, subtitle parsing, and website/book imports.
3. **Frontend (Vue 2 + Vuetify + Vuex)**: SPA components compiled via Laravel Mix (`webpack.mix.js`) providing an interactive reading, vocabulary lookup, and reviewing interface. *Note: An experimental Vue 3 + Vite + Tailwind migration project is located in `resources/vue3/`.*

---

## 2. Directory Structure Overview
- **`app/`**: Core backend application logic (Laravel).
  - **`app/Models/`**: Eloquent models representing database tables (`Book`, `Chapter`, `EncounteredWord`, `Phrase`, `Goal`, etc.).
  - **`app/Services/`**: The core business logic layer. Controllers delegate complex operations to these services (e.g., [TextBlockService](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/TextBlockService.php), [ChapterService](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/ChapterService.php), [VocabularyService](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/VocabularyService.php), [DictionaryService](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/DictionaryService.php), [AnkiApiService](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/AnkiApiService.php)).
  - **`app/Http/Controllers/`**: Request validation, access authorization, and service call coordination.
  - **`app/Jobs/`**: Background worker queue jobs (e.g., `ProcessChapter` for async tokenizing and indexing of texts).
- **`config/`**: Configuration files. [linguacafe.php](file:///c:/q/git/linguacafe/LinguaCafe/config/linguacafe.php) contains application settings, including supported source and target languages, translation API mappings, and tokenization settings.
- **`database/`**: Database migrations, seeders, and factories.
- **`docker/`**: Contains Dockerfiles for PHP and Python services in dev and prod environments.
- **`manual/`**: Markdown manuals for installation and usage.
- **`resources/`**: Frontend assets.
  - **`resources/js/`**: Active Vue 2 codebase (Vuex stores, themes, layouts, and components).
  - **`resources/vue3/`**: Experimental Vue 3 transition setup.
  - **`resources/views/`**: Blade template files (`app.blade.php`).
- **`routes/`**: Route definitions. [web.php](file:///c:/q/git/linguacafe/LinguaCafe/routes/web.php) defines all web views and backend API routes.
- **`tests/`**: Test suite containing `Feature/Auth` and `Unit` tests.
- **`tools/`**: Helper tools such as Python tokenizer service source [tokenizer.py](file:///c:/q/git/linguacafe/LinguaCafe/tools/tokenizer.py) and Japanese conjugation dictionaries.

---

## 3. Core Workflows & Logic

### A. Book & Chapter Processing
1. **Importing**:
   - `ImportService.php` sends import files or texts to the Python Tokenizer Service container via `/tokenizer/import-book` or `/tokenizer/import-text`.
   - Text is split into chunks, saved as unprocessed `Chapter` records, and processed asynchronously via the `ProcessChapter` Job.
2. **Tokenization and Indexing**:
   - The `ProcessChapter` job calls `ChapterService::processChapterText()`.
   - It utilizes `TextBlockService` to send text to the Python tokenizer `/tokenizer` endpoint.
   - spaCy tokenizes the text into words, POS tags, and lemmas, customized per language (e.g., combining adjacent verbs and auxiliary words in Japanese, adding grammatical indicators in German/Norwegian).
   - `TextBlockService::createNewEncounteredWords()` checks which words are seen for the first time by the user and inserts them into the `encountered_words` table with default `stage = 2` (New).
   - Any multi-word `Phrase` records defined by the user are matched and tagged using `TextBlockService::updatePhraseIds()`.
   - The processed words are compressed (using `gzcompress`) and stored in `chapters.processed_text`.

### B. Vocabulary & SRS (Spaced Repetition System)
- **`EncounteredWord` & `Phrase` stages**:
  - `stage = 2`: New word/phrase.
  - `stage = 0`: Known word/phrase.
  - `stage = 1`: Ignored/skipped (special characters/stop words).
  - `stage < 0` (e.g. `-1, -2, -3...`): SRS review stages.
- The `setStage()` method on `EncounteredWord` updates achievements, sets `relearning` flags, and calculates the `next_review` date using configurable review intervals (`reviewIntervals` setting).

### C. Dictionaries & Integrations
- **JMDict / KANJIDIC2**: Embedded Japanese dictionary and radical lookup.
- **APIs**:
  - **DeepL API**: Contextual/machine translations.
  - **MyMemory / LibreTranslate**: Online translation alternatives.
  - **AnkiConnect**: Communicates with local Anki via `AnkiApiService.php` to export words/phrases as flashcards.
  - **Jellyfin**: Pulls subtitles dynamically using Jellyfin APIs (`JellyfinService.php`).

---

## 4. Test Suite
- Run suite using standard Laravel testing commands:
  ```bash
  php artisan test
  ```
- **Feature Tests**: Located in `tests/Feature/Auth` testing authentication paths (login, email verification, registration, password resets).
- **Unit Tests**: Minimal default setups in `tests/Unit`.

---

## 5. External Integrations & Troubleshooting Hotspots
| Integrations | Key Service File | Port / Endpoint |
|---|---|---|
| Python Tokenizer | [TextBlockService.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/TextBlockService.php) | Port `8678` (`/tokenizer`, `/tokenizer/subtitle`) |
| Anki Connect | [AnkiApiService.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/AnkiApiService.php) | Port `8765` |
| DeepL Translation | [DictionaryService.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/DictionaryService.php) | `api-free.deepl.com` |
| MyMemory Translation | [DictionaryService.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/DictionaryService.php) | `api.mymemory.translated.net` |
| Jellyfin Subtitles | [JellyfinService.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/JellyfinService.php) | Port configured in user settings |

---

This document should be updated whenever directories are modified, new services are added, or major architectural changes take place.
