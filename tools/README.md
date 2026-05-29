# LinguaCafe Tools Overview (`tools/`)

This directory contains external non-PHP scripts, primarily the Python tokenization backend microservice and dictionary parsing utilities.

---

## 1. Directory Structure

### ⚙️ [tokenizer.py](file:///c:/q/git/linguacafe/LinguaCafe/tools/tokenizer.py)
A Python microservice built using the **Bottle WSGI framework** (listening on port `8678`). It processes text requests sent by the PHP backend. Core API endpoints include:
* `/tokenizer` (POST): Tokenizes input text blocks using **spaCy** pipelines (e.g. lemmas, POS tags, and genders). It implements:
  * Dynamic model loading on first request.
  * Custom sentence boundary detector splitting (`custom_sentence_splitter` pipeline component).
  * Phonetic transcriptions: **pykakasi** for Japanese hiragana conversions and **pinyin** for Chinese.
  * German separable verbs handling.
* `/tokenizer/subtitle` (POST): Parses subtitle array maps, returning token alignments alongside start/end timestamp objects.
* `/tokenizer/import-book` / `/tokenizer/import-text` / `/tokenizer/import-subtitles` (POST): Loads EPUB structures, processes HTML entities, splits raw text into sentence lists, and partitions them into readable chunk arrays.
* `/tokenizer/get-youtube-subtitle-list` / `/tokenizer/get-subtitle-file-content` / `/tokenizer/get-website-text` (POST): Extracts transcripts from YouTube URLs, parses local subtitle files, and downloads clean body content from news websites using the `newspaper3k` library.
* `/models/install` (POST), `/models/list` (GET), `/models/remove` (DELETE): Manages download, extraction, and removal of large NLP language models dynamically at runtime by executing shell commands targeting `/var/www/html/storage/app/model`.

### 📁 [jmdict_conjugation/](file:///c:/q/git/linguacafe/LinguaCafe/tools/jmdict_conjugation)
Contains tools to handle conjugation mapping for the Japanese dictionary (JMDict).
* **`conj.py`**: Implementation of Japanese verb, adjective, and auxiliary conjugation rules.
* **`jmdict_conjugation.py`**: Entry script to parse dictionary terms and output conjugated mappings.
