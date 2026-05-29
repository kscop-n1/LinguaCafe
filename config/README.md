# LinguaCafe Configuration Layer Overview (`config/`)

This directory houses all backend configuration settings. Standard framework configs are tuned for queues, caching, databases, and WebSockets.

---

## 1. Primary Configuration Files

### ⚙️ [linguacafe.php](file:///c:/q/git/linguacafe/LinguaCafe/config/linguacafe.php)
The central, custom configuration file specifically for the LinguaCafe app. It defines:
* **`supported_languages`**: List of source languages that can be read.
* **`supported_languages_with_required_install`**: Languages requiring pre-downloaded local assets (e.g. Japanese, Chinese, Russian, Turkish, Korean, Ukrainian, Thai).
* **`languages_without_spaces`**: Languages that do not use space separators (e.g. Chinese, Japanese, Thai).
* **`supported_target_languages`**: Languages that can be translated into, mapped to flag files.
* **`deepl_supported_target_languages` & `my_memory_supported_target_languages`**: API key mappings and translation limits.
* **`words_to_skip`**: Array of characters, punctuation marks, and symbols ignored during word counting and vocabulary highlights.
* **`tokens_with_no_space_before` / `tokens_with_no_space_after`**: Space adjustments rules used by the tokenizer output.

### ⚙️ [database.php](file:///c:/q/git/linguacafe/LinguaCafe/config/database.php)
Defines connection credentials and configurations for relational storage.
* Configures **MySQL** (`mysql`) by default for production environments.
* Configures **Redis** (`redis`) connections to manage job queue brokers and cache tracking.

### ⚙️ [queue.php](file:///c:/q/git/linguacafe/LinguaCafe/config/queue.php) & [horizon.php](file:///c:/q/git/linguacafe/LinguaCafe/config/horizon.php)
Configures asynchronous task queues.
* Links to **Redis** queues by default to handle slow operations like text tokenization.
* `horizon.php` sets up dashboard monitoring controls for queues.

### ⚙️ [reverb.php](file:///c:/q/git/linguacafe/LinguaCafe/config/reverb.php) & [broadcasting.php](file:///c:/q/git/linguacafe/LinguaCafe/config/broadcasting.php)
Configures WebSockets routing for pushing real-time messages to the client.
* Integrates **Laravel Reverb** or **Pusher** channels.

### ⚙️ [app.php](file:///c:/q/git/linguacafe/LinguaCafe/config/app.php) & [auth.php](file:///c:/q/git/linguacafe/LinguaCafe/config/auth.php)
* Standard framework variables: Timezones, locales, encryptions, authentication providers, and guards.

---

## 2. Environment Variables (.env)
Many configs depend on `.env` variables. Key keys include:
* `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (MySQL config)
* `QUEUE_CONNECTION` (Defaults to `redis` in production, `sync` in testing)
* `PYTHON_CONTAINER_NAME` (Defaults to `linguacafe-python-service`, defines spaCy parser hostname)
