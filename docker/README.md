# LinguaCafe Containerization Overview (`docker/`)

This directory contains the Docker configuration scripts used to build and orchestrate the LinguaCafe self-hosted environment.

---

## 1. Directory Structure

* [PhpDockerfile](file:///c:/q/git/linguacafe/LinguaCafe/docker/PhpDockerfile) & [PhpDockerfileDev](file:///c:/q/git/linguacafe/LinguaCafe/docker/PhpDockerfileDev): Docker builds for the Laravel runtime.
  * Extends standard `php:8.2-apache`.
  * Installs core utilities (`curl`, `zip`, `unzip`, `sqlite3`, `supervisor`, `default-mysql-client`).
  * Configures PHP parameters (`memory_limit = 500M`, `post_max_size = 500M`, `max_execution_time = 600`) to support large book imports.
  * Copies virtual host maps [vhost.conf](file:///c:/q/git/linguacafe/LinguaCafe/docker/vhost.conf) and runs Artisan optimization.
  * Serves via **supervisord** config to run Laravel Horizon workers and WebSocket broadcaster in parallel.
* [PythonDockerfile](file:///c:/q/git/linguacafe/LinguaCafe/docker/PythonDockerfile) & [PythonDockerfileDev](file:///c:/q/git/linguacafe/LinguaCafe/docker/PythonDockerfileDev): Docker builds for the tokenizer microservice.
  * Extends `ubuntu:22.04` runtime.
  * Installs Python 3 and dependencies via `pip` (`spacy`, `pykakasi`, `pinyin`, `ebooklib`, `youtube_transcript_api`, `newspaper3k`).
  * Downloads pre-trained spaCy NLP language models for 20+ supported languages (e.g. `en_core_web_sm`, `de_core_news_sm`, `es_core_news_sm`, `xx_ent_wiki_sm`).
* [vhost.conf](file:///c:/q/git/linguacafe/LinguaCafe/docker/vhost.conf): Apache virtual host configuration pointing root routes to the Laravel public directory `/var/www/html/public`.

---

## 2. Docker Compose Orchestration

The application is brought up via `docker-compose.yml` (located in the workspace root) defining 4 interconnected services:

1. **`webserver`** (`linguacafe-webserver`): Runs the Apache + PHP runtime on port `9191` (configurable), exposing port `6001` for real-time WebSocket communication.
2. **`mysql`** (`linguacafe-database`): Runs MySQL 8.0 on port `3306` inside the bridged bridge network.
3. **`redis`** (`linguacafe-redis`): Runs Redis 7.2 to serve as both cache broker and queue queue driver for background Horizon workers.
4. **`python`** (`linguacafe-python-service`): Runs the tokenizer service by executing `python3 /app/tokenizer.py`.

---

## 3. Operations Commands

* **Start the Stack**:
  ```bash
  docker compose up -d
  ```
* **Stop the Stack**:
  ```bash
  docker compose down
  ```
* **Rebuild Dev Services**:
  ```bash
  docker compose -f docker-compose-dev.yml build
  ```
