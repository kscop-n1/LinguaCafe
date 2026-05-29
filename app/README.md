# LinguaCafe Application Layer Overview (`app/`)

This directory contains the core backend application logic for LinguaCafe, structured as a standard Laravel application utilizing a Service-Oriented architecture.

---

## 1. Directory Structure

### 📁 [Console](file:///c:/q/git/linguacafe/LinguaCafe/app/Console)
Handles command-line utilities.
* **`Commands/`**: Contains custom Artisan commands.
  * [CreateBackup.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Console/Commands/CreateBackup.php): Runs database and file backup tasks.

### 📁 [Enums](file:///c:/q/git/linguacafe/LinguaCafe/app/Enums)
Contains backend enums used throughout the application.
* [ChapterProcessingStatusEnum.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Enums/ChapterProcessingStatusEnum.php): Tracks processing state of text chapters (`unprocessed`, `processed`, `failed`).

### 📁 [Events](file:///c:/q/git/linguacafe/LinguaCafe/app/Events)
Defines WebSocket broadcasting events for real-time progress/status reporting to the frontend.
* [ChapterStateUpdatedEvent.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Events/ChapterStateUpdatedEvent.php): Broadcasts status and word count changes as chapters are processed.
* [DictionaryImportProgressedEvent.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Events/DictionaryImportProgressedEvent.php): Informs the frontend of dictionary import progress.

### 📁 [Http](file:///c:/q/git/linguacafe/LinguaCafe/app/Http)
Manages HTTP requests, routing middleware, and API endpoints.
* **`Controllers/`**: Thin controller classes that map incoming web/API endpoints to specialized service layer tasks. Key controllers include:
  * [ChapterController.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Http/Controllers/ChapterController.php): Controls chapter retrieval, creation, updating, and marking finished.
  * [DictionaryController.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Http/Controllers/DictionaryController.php): Dictates dictionary settings, CSV imports, and lookup queries.
  * [VocabularyController.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Http/Controllers/VocabularyController.php): Vocabulary updates, search queries, and custom translations.
* **`Middleware/`**: Request filters.
  * [AdminMiddleware.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Http/Middleware/AdminMiddleware.php): Restricts administrative settings (e.g. dictionary imports, user management) to admin roles.
* **`Requests/`**: Strong request validation files. They are grouped into domain-specific subdirectories (e.g. `Vocabulary/`, `Review/`, `Chapters/`) to validate incoming JSON payloads before they hit the controller logic.

### 📁 [Jobs](file:///c:/q/git/linguacafe/LinguaCafe/app/Jobs)
Manages background queue tasks.
* [ProcessChapter.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Jobs/ProcessChapter.php): Background job that tokenizes, parses, matches user phrases, and indexes raw text for reading.

### 📁 [Models](file:///c:/q/git/linguacafe/LinguaCafe/app/Models)
Eloquent database models that represent the schema and relations.
* [Book.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Models/Book.php): A collection of chapters configured for a target language.
* [Chapter.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Models/Chapter.php): Represents a chunk of text, storing both its `raw_text` and a compressed JSON structure `processed_text`.
* [EncounteredWord.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Models/EncounteredWord.php): Core table tracking each word known/highlighted/reviewed by the user. Includes helper methods like `setStage()` to calculate reviews using the Spaced Repetition System.
* [Phrase.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Models/Phrase.php): Tracks custom multi-word phrases created by users.
* [Goal.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Models/Goal.php) & [DailyAchievement.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Models/DailyAchievement.php): Track daily reading and vocabulary acquisition milestones.

### 📁 [Providers](file:///c:/q/git/linguacafe/LinguaCafe/app/Providers)
Bootstrap configuration services.
* [RouteServiceProvider.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Providers/RouteServiceProvider.php): Binds routing directories.

### 📁 [Services](file:///c:/q/git/linguacafe/LinguaCafe/app/Services)
The core business logic layer. Controllers delegate complex algorithms and third-party integrations to these services:
* [TextBlockService.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/TextBlockService.php): Communicates with the spaCy tokenizer microservice, cleans token mappings, identifies user-defined phrases, and prepares text payloads for Vue readers.
* [ChapterService.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/ChapterService.php): Handles chapter CRUD commands, processes raw text data, and updates book stats.
* [VocabularyService.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/VocabularyService.php): Search, export, and import of CSV vocab logs.
* [DictionaryService.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/DictionaryService.php) & [DictionaryImportService.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/DictionaryImportService.php): Manage local dictionary definitions (JMDict, Dict.cc files, etc.) and query external translation APIs (DeepL, MyMemory).
* [AnkiApiService.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Services/AnkiApiService.php): Implements AnkiConnect protocol to synchronize words as flashcards.

---

## 2. Core Architectural Design Patterns

1. **Service Delegation**: Controllers are designed to be thin, serving primarily to validate requests via `Requests/` classes and call a corresponding service class in `Services/` to return payloads.
2. **Asynchronous Text Ingestion**: To ensure large imports do not hang web responses, text processing is delegated to Laravel Queues (implemented by [ProcessChapter.php](file:///c:/q/git/linguacafe/LinguaCafe/app/Jobs/ProcessChapter.php)).
3. **Database Performance via Compression**: Since processed token objects containing indices and grammatical info are heavy, they are compressed via `gzcompress` when stored in `Chapter` database records and decompressed on read.
