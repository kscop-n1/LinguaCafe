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

---

## 4. Versioned Release Candidate Flow

Production `docker-compose.yml` uses GitHub Container Registry images by default:

* `ghcr.io/kscop-n1/linguacafe-webserver:${VERSION:-latest}`
* `ghcr.io/kscop-n1/linguacafe-python-service:${VERSION:-latest}`

The `webserver` and `python` services are built by this repository. MySQL and Redis use upstream images:

* `mysql:8.0`
* `redis:7.2-alpine`

The current migration release-candidate tag is:

```bash
VERSION=v0.6.0-rc1
```

The production image does not contain a `.env` file. Set `APP_KEY` in the deployment `.env` before starting the webserver:

```bash
printf 'APP_KEY=base64:%s\n' "$(openssl rand -base64 32)" >> .env
```

Keep an existing `APP_KEY` value when updating an existing install.

### Build RC Images Locally

Use this when testing an RC before published GHCR images are available:

```bash
git fetch --tags
git checkout v0.6.0-rc1
test -n "$APP_KEY" || export APP_KEY="base64:$(openssl rand -base64 32)"
VERSION=v0.6.0-rc1 docker compose build --pull webserver python
VERSION=v0.6.0-rc1 docker compose up -d
VERSION=v0.6.0-rc1 docker compose logs -f --tail=100
```

This builds the local images with the same names that production compose uses:

* `ghcr.io/kscop-n1/linguacafe-webserver:v0.6.0-rc1`
* `ghcr.io/kscop-n1/linguacafe-python-service:v0.6.0-rc1`

### Publish RC Images

After the `v0.6.0-rc1` tag is pushed, publish the images through the existing GitHub Actions workflow:

```bash
git push origin main
git push origin v0.6.0-rc1
gh workflow run build-and-push.yml --ref v0.6.0-rc1
gh run watch
```

The workflow publishes:

* `ghcr.io/kscop-n1/linguacafe-webserver:v0.6.0-rc1`
* `ghcr.io/kscop-n1/linguacafe-python-service:v0.6.0-rc1`
* `ghcr.io/kscop-n1/linguacafe-webserver:latest`
* `ghcr.io/kscop-n1/linguacafe-python-service:latest`

### Update A Docker Compose Install To The RC

For an install that uses published GHCR images, set `VERSION` in the deployment directory and pull the matching images:

```bash
cd /home/serhii/docker/linguacafe
printf 'VERSION=v0.6.0-rc1\n' >> .env
printf 'APP_KEY=base64:%s\n' "$(openssl rand -base64 32)" >> .env
docker compose pull
docker compose up -d
docker compose logs -f --tail=100
```

If `.env` already contains `VERSION=...` or `APP_KEY=...`, edit those lines instead of appending duplicates. Do not rotate `APP_KEY` for an existing install unless you intentionally want to invalidate encrypted app data and sessions.

### Verify The Running RC

```bash
docker exec linguacafe-webserver test -s /var/www/html/public/build/manifest.json
docker exec linguacafe-webserver php -r 'require "/var/www/html/vendor/autoload.php"; echo "autoload-ok\n";'
curl -I http://127.0.0.1:${PORT:-9191}/login
docker compose ps
```

The webserver image is expected to contain production Vite assets in `/var/www/html/public/build`, including `manifest.json`.

### Roll Back From The RC

To return to the previous published default image:

```bash
cd /home/serhii/docker/linguacafe
sed -i '/^VERSION=/d' .env
docker compose pull
docker compose up -d
docker compose logs -f --tail=100
```

To roll back to a specific prior version:

```bash
cd /home/serhii/docker/linguacafe
sed -i 's/^VERSION=.*/VERSION=v0.5.48/' .env
docker compose pull
docker compose up -d
docker compose logs -f --tail=100
```
