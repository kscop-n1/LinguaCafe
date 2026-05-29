# LinguaCafe Documentation Layer Overview (`manual/`)

This directory contains the user manuals, setup guides, and FAQs written in Markdown.

---

## 1. Directory Structure

* [Home.md](file:///c:/q/git/linguacafe/LinguaCafe/manual/Home.md): Core introduction, background context on why LinguaCafe was developed, and list of alternative self-hosted reading platforms (LWT, Lute).
* [Setup.md](file:///c:/q/git/linguacafe/LinguaCafe/manual/Setup.md): Comprehensive instructions on installation (Docker, Apple Silicon, database settings, migrations), dictionary imports (JMDict, CC-CEDICT, Wiktionary, etc.), and Anki setup.
* [Usage and features.md](file:///c:/q/git/linguacafe/LinguaCafe/manual/Usage and features.md): Guides explaining reading features, creating flashcards, custom phrases, daily goals, and settings.
* [FAQ.md](file:///c:/q/git/linguacafe/LinguaCafe/manual/FAQ.md): Lists solutions to typical installation and runtime issues.
* [Miscellaneous.md](file:///c:/q/git/linguacafe/LinguaCafe/manual/Miscellaneous.md): Additional background details.

---

## 2. In-App User Manual System

These files do not just exist for standalone reference. The Laravel application dynamically reads these Markdown files and serves them to the frontend user manual tab.
* **Routing Endpoint**: Configured in [web.php](file:///c:/q/git/linguacafe/LinguaCafe/routes/web.php):
  * `/manual/get-menu-tree` calling `HomeController::getUserManualTree()` to compile the directory structure.
  * `/manual/get-manual-file/{fileName}` calling `HomeController::getUserManualFile()` to read the Markdown content.
* **Frontend View**: Component `UserManual.vue` requests these files and renders them as HTML using the `vue-showdown` library.
