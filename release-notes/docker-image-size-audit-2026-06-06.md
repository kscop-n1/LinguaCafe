# Docker image size audit - 2026-06-06

## Scope

Audited the Docker build and runtime setup for these LinguaCafe services:

- `webserver`
- `python`
- `mysql`
- `redis`

Priority order used: runtime stability, reproducible builds, maintainability/diagnostics, security, then image size and build time.

## Current baseline

Measurements were taken on an amd64 Linux host with Docker Buildx v0.34.1. Docker `Size` values are unpacked local image sizes, not compressed registry transfer sizes. `docker system df -v` showed that shared and unique layers matter: for example the current local `ghcr.io/kscop-n1/linguacafe-webserver:latest` displayed 845 MB total but about 499 MB was shared and about 345 MB was unique on this host.

| Service | Image | Source | Architecture | Local size | Layers | Notes |
| --- | --- | --- | --- | ---: | ---: | --- |
| webserver | `ghcr.io/kscop-n1/linguacafe-webserver:latest` | Built by this repo from `docker/PhpDockerfile` | amd64 | 845 MB | 25 | Production PHP/Apache/Laravel image. |
| python | `ghcr.io/kscop-n1/linguacafe-python-service:latest` | Built by this repo from `docker/PythonDockerfile` | amd64 | 1.24 GB | 7 | NLP/tokenizer image with spaCy models and language tooling. |
| mysql | `mysql:8.0` | Upstream official image, not modified by this repo | amd64 | 799 MB | 11 | Database reliability image; largest upstream layer is MySQL Shell. |
| redis | `redis:7.2-alpine` | Upstream official image, not modified by this repo | amd64 | 38.6 MB | 7 | Small upstream Alpine image. |

A separate local `redis:alpine` image of 114 MB existed on the host, but it is not the tag used by `docker-compose.yml`.

## Largest layers

### webserver before optimization

A local baseline rebuild from the original Dockerfile produced `linguacafe-audit-web-before`:

- Size: 886,856,103 bytes, shown as 887 MB by Docker
- Layers: 25
- Build context transfer: 1.97 MB
- Cached/local baseline build duration: 26.28s

Largest relevant layers:

- 347 MB inherited from `php:8.2-apache` base PHP build tooling layer.
- 232 MB apt runtime/development package layer from this Dockerfile.
- 37.5 MB Composer production dependencies.
- 31.9 MB chown/user layer.
- 25.5 MB application source copy.
- 6.37 MB frontend public assets copied from the Node builder.

### python

Largest layers in the current production Python image:

- 670 MB: bundled spaCy language model downloads.
- 390 MB: Python packages installed with pip.
- 102 MB: Ubuntu Python/pip/tzdata packages.
- 78.1 MB: Ubuntu 22.04 base rootfs.

The Python image includes expected tokenizer/runtime dependencies used by `tools/tokenizer.py`, including spaCy, pykakasi, pinyin, ebooklib, youtube transcript support, subtitle parsing, and newspaper extraction. It also supports runtime installation of additional language models into mounted storage.

### mysql

Largest upstream layers:

- 522 MB: `mysql-shell` package layer.
- 147 MB: MySQL server minimal package layer.
- 113 MB: Oracle Linux slim base rootfs.

This repository does not modify the MySQL image.

### redis

Largest upstream layer:

- 30.2 MB: Redis build/install runtime layer.

This repository does not modify the Redis image.

## Implemented low-risk changes

### 1. Reduced production webserver runtime packages

Removed `nodejs` from the final PHP/Apache runtime image. Node remains in the `node_build` stage where frontend assets are compiled. Runtime verification found no production app use of Node/npm; Laravel serves already-built Vite assets from `public/build/manifest.json`.

Kept these tools intentionally:

- Composer: useful for operational diagnostics and already present before this audit.
- `mysqldump` / `mariadb-dump`: required by the backup flow.
- Supervisor: required to run Apache, Horizon, backup scheduler, and websockets in the container.
- SQLite/zip/unzip and PHP extension build inputs: left unchanged except for the low-risk duplicate install cleanup.

### 2. Improved frontend dependency build determinism and cache reuse

Changed the Node builder stage from:

```dockerfile
COPY ./ /build
RUN npm install && npm run prod
```

to:

```dockerfile
COPY package.json package-lock.json ./
RUN npm ci
COPY ./ /build
RUN npm run prod
```

Benefits:

- Uses the lockfile strictly for reproducible frontend installs.
- Lets the `npm ci` layer be reused when only application source changes.
- Keeps Node/npm out of the final runtime image.

### 3. Removed duplicate PHP extension install

The production Dockerfile installed `pdo_mysql` once in the grouped install and then again as a standalone command. The duplicate standalone install was removed. This is mainly a build-time cleanup; final size did not materially change because the duplicate compiled artifact overwrote the same extension path.

### 4. Tightened Docker build context

Extended `.dockerignore` to exclude local-only and documentation/report files that are not required for image builds or runtime:

- `.agents`, `.codex`, `.phpunit.result.cache`, `supervisord.pid`
- `GithubImages`, `release-notes`, `tests`
- compose files, markdown/audit docs, local reports, temporary/log/cache outputs

Kept runtime/build inputs such as app source, migrations, config, public assets, `tools`, and storage skeletons.

Measured context transfer changed from 1.97 MB to about 56-57 kB for the webserver build.

### 5. Added GitHub Actions BuildKit cache

Added `cache-from`/`cache-to` using GitHub Actions cache backend for:

- release image workflow: `build-and-push.yml`
- beta image workflow: `beta-image.yml`
- test image workflow: `test-image.yml`

Expected benefit: faster repeated CI builds, especially after the first cache-producing run. This reduces rebuild time more than final image size.

## Before / after measurements

### webserver local rebuild

| Metric | Before | After | Change |
| --- | ---: | ---: | ---: |
| Local image size | 886,856,103 bytes | 781,390,271 bytes | -105,465,832 bytes |
| Docker displayed size | 887 MB | 781 MB | about -106 MB |
| Layer count | 25 | 25 | no change |
| Build context transfer | 1.97 MB | 56.63 kB | much smaller |
| Clean no-cache build after changes | n/a | 1m 05.97s | final Dockerfile measured locally |
| Fully cached rebuild after changes | n/a | 0.80s | all layers reused |

Final optimized webserver layer highlights:

- Apt package layer dropped from about 232 MB to about 130 MB after removing runtime Node.
- PHP base remains the dominant inherited cost at about 347 MB plus other PHP base layers.
- App copy remains small compared with runtime dependencies.

### python

No Dockerfile change was implemented. The 1.24 GB size is mostly justified by bundled NLP language models and Python tokenizer dependencies. Removing models or switching to a much smaller base would be a product/runtime compatibility change, not a safe low-risk audit cleanup.

### mysql

No change implemented. `mysql:8.0` is an upstream database image; customizing it to remove MySQL Shell or change the base image is not recommended for this task because database reliability and supportability outweigh image-size reduction.

### redis

No change implemented. `redis:7.2-alpine` is already small at 38.6 MB and is an upstream image.

## Verification performed

### Image build and runtime verification

Built optimized webserver image locally:

```bash
docker buildx build --no-cache --progress=plain --load -t linguacafe-audit-web-after -f docker/PhpDockerfile .
```

Verified inside the optimized webserver image:

- PHP extensions present: `fileinfo`, `gd`, `mysqli`, `pcntl`, `pdo_mysql`, `zip`.
- `node` absent from final runtime image.
- `mysqldump` and `mariadb-dump` present for backup support.
- `public/build/manifest.json` exists and is non-empty.

### Isolated stack verification

Started a disposable Compose stack using:

- `linguacafe-audit-web-after:latest`
- `ghcr.io/kscop-n1/linguacafe-python-service:latest`
- `mysql:8.0`
- `redis:7.2-alpine`

The stack used disposable Docker volumes under the temporary project `linguacafeimageaudit`; production and dev database directories were not mounted.

Verified:

- MySQL healthcheck became healthy.
- Laravel migrations and seeders completed.
- Supervisor started Apache, backup scheduler, Horizon, and websockets.
- `GET /login` returned HTTP 200.
- Redis returned `PONG`.
- MySQL contained the expected migrated tables.
- Python tokenizer endpoint responded successfully using the application payload contract (`raw_text`, lowercase language key).
- `php artisan app:create-backup` created a non-empty SQL backup file inside the disposable storage volume.

The disposable stack and volumes were removed with:

```bash
docker compose -f /tmp/linguacafe-image-audit-compose.yml down -v
```

## Proposed optimizations not implemented

### Low risk, implemented

- Remove runtime Node from production webserver image.
- Use `npm ci` with lockfile-first copy in the frontend builder stage.
- Exclude local-only docs/reports/tests from Docker build contexts.
- Add GitHub Actions BuildKit cache.
- Remove duplicate `pdo_mysql` install.

### Medium risk, not implemented

- Rework Composer install into a separate dependency layer with `composer install --no-scripts --no-autoloader` before source copy, then run autoload/package discovery after source copy. This could improve repeated webserver builds, but Laravel Composer scripts depend on app/artisan context, so this should be tested separately.
- Split PHP extension compilation into a custom builder/runtime copy flow. The current `php:8.2-apache` base already carries large inherited layers, so the benefit may be limited unless the base image strategy changes.
- Move Python to a slimmer Debian/Python base and remove `python3-dev`. This could reduce some size, but native Python dependencies and spaCy model compatibility need a dedicated compatibility test matrix.

### Not recommended for this task

- Removing bundled spaCy language models from the Python image. Most of the Python image size is language support, and removing models changes product behavior.
- Switching Python or PHP to Alpine only for size. Native extensions, spaCy/model compatibility, libc behavior, font/locale/certificate support, and debugging implications make this too risky for an audit-first cleanup.
- Customizing `mysql:8.0` to remove upstream components. Database reliability and upstream support are more important than image size.
- Replacing `redis:7.2-alpine`. It is already small and upstream-supported.
- Removing backup dump clients from the webserver image. They are required by the database backup feature.

## Remaining notes

- The PHP base image is still the dominant webserver size contributor. Further large reductions likely require a broader base-image strategy, not a simple Dockerfile cleanup.
- The Python image is large by design because tokenizer language support is bundled. A separate product decision would be needed to make models optional or install-on-demand by default.
- Registry transfer size was not directly measured here. Local Docker image size is unpacked size; compressed GHCR transfer size will be smaller and depends on layer compression and cache state.
