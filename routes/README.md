# LinguaCafe Routing Layer Overview (`routes/`)

This directory contains route mappings that connect HTTP requests, console actions, and WebSocket connections to application handlers.

---

## 1. Primary Routing Files

### 🕸️ [web.php](file:///c:/q/git/linguacafe/LinguaCafe/routes/web.php)
The central routing file mapping all web layouts and application endpoints.
* **Guest Routes**: Maps entry URLs `/login` and registration scripts `/users/create`.
* **Authenticated API Groups** (protected by `auth`, `auth.session`, and `web` middleware):
  * **Admin settings** (further protected by `admin` middleware): Endpoint mappings for system backups, user updates, font uploads, dictionary imports, and target language setups.
  * **User actions**: Target language selection dialogs, password changes, and user manuals.
  * **Application operations**: Endpoints for book lists (`/books`), chapter data loading (`/chapters/get/reader`), vocabulary updates (`/vocabulary/word/update`), translation lookup operations, and Anki card syncs.

### 🕸️ [auth.php](file:///c:/q/git/linguacafe/LinguaCafe/routes/auth.php)
Contains Laravel Breeze/standard authentication controller routes (e.g. forgot password, verify emails, reset passwords, and logout routines).

### 🕸️ [channels.php](file:///c:/q/git/linguacafe/LinguaCafe/routes/channels.php)
Configures event authorization channels for WebSockets/broadcasting.
* `dictionary-import-progress.{userUuid}`: Validates authorization so users can only monitor their own dictionary imports.
* `chapter-status-update.{userUuid}`: Validates authorization so users can only monitor their own chapter imports.

### 🕸️ [api.php](file:///c:/q/git/linguacafe/LinguaCafe/routes/api.php) & [console.php](file:///c:/q/git/linguacafe/LinguaCafe/routes/console.php)
* `api.php`: Declares Sanctum token auth endpoint mapping (largely unused as the main SPA runs via the stateful session `web.php` group).
* `console.php`: Maps console execution tasks (such as recurring cron tasks).
