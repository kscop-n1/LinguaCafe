# Docker Release Candidate Readiness - 2026-07-01

## Scope

This audit covered the Docker release path for `v0.6.0-rc1` only:

- Production and development Compose files: `docker-compose.yml`, `docker-compose-dev.yml`, `docker-compose-dev-macos.yml`
- Image builds: `docker/PhpDockerfile`, `docker/PythonDockerfile`, development Dockerfiles, `.dockerignore`
- Runtime startup: `entrypoint.sh`, `config/supervisord.conf`, Apache vhost config, supervisor helper configs
- Release automation: `.github/workflows/build-and-push.yml`, `test-image.yml`, `beta-image.yml`
- Docker documentation and install/update notes

No production data maintenance, cleanup, backfill, phrase repair, or non-Docker UI regression batch was run.

## Service Topology

Production `docker-compose.yml` runs four services on the `linguacafe` bridge network:

- `webserver`: `ghcr.io/kscop-n1/linguacafe-webserver:${VERSION:-latest}`
- `mysql`: upstream `mysql:8.0`
- `redis`: upstream `redis:7.2-alpine`
- `python`: `ghcr.io/kscop-n1/linguacafe-python-service:${VERSION:-latest}`

The `webserver` image contains the Laravel app, optimized Composer dependencies, Apache, supervisord, Horizon, Reverb, and production Vite assets under `/var/www/html/public/build`.

The `python` image contains the tokenizer runtime and NLP dependencies and shares the Laravel `storage` mount with the webserver.

## RC Tag Strategy

The existing repository version convention supports using:

```bash
VERSION=v0.6.0-rc1
```

Expected RC image tags:

```bash
ghcr.io/kscop-n1/linguacafe-webserver:v0.6.0-rc1
ghcr.io/kscop-n1/linguacafe-python-service:v0.6.0-rc1
```

The GitHub workflow also tags `latest` when it publishes from the release/tag workflow. Pin `VERSION=v0.6.0-rc1` for RC testing so a later `latest` movement cannot change the tested image unexpectedly.

## Docker Changes Required By The Audit

The audit found and fixed release-blocking Docker issues:

- `docker-compose.yml` now includes `build` metadata for `webserver` and `python`, so the same production image names can be built locally with `VERSION=v0.6.0-rc1 docker compose build webserver python`.
- `docker-compose.yml` now explicitly passes required `APP_KEY` into the production webserver container. The image excludes `.env`, so fresh Docker installs must provide this value.
- `entrypoint.sh` now creates and owns required storage folders and log files when a fresh bind-mounted `storage` directory is used.
- `docker/PhpDockerfile` now returns to root before running the entrypoint so it can prepare bind-mounted storage, while app services still run as the `laravel` user.
- `docker/PhpDockerfile` now sets Apache workers to the `laravel` user/group so web requests can write Laravel storage/log files.
- `config/supervisord.conf` now runs supervisord intentionally as root and lets Apache start through the standard root parent process; Horizon, Reverb, and backups remain under `laravel`.

## Local RC Build

Build and test the RC images locally before published GHCR images are available:

```bash
git fetch --tags
git checkout v0.6.0-rc1
test -n "$APP_KEY" || export APP_KEY="base64:$(openssl rand -base64 32)"
VERSION=v0.6.0-rc1 docker compose build --pull webserver python
VERSION=v0.6.0-rc1 docker compose up -d
VERSION=v0.6.0-rc1 docker compose logs -f --tail=100
```

## Publish RC Images

After the RC tag is pushed:

```bash
git push origin main
git push origin v0.6.0-rc1
gh workflow run build-and-push.yml --ref v0.6.0-rc1
gh run watch
```

The manual workflow dispatch is kept in the documented path because previous Docker publishes for this repository have required explicitly dispatching `build-and-push.yml` on the tag.

## Update A Docker Compose Install To The RC

For a GHCR-based install:

```bash
cd /home/serhii/docker/linguacafe
printf 'VERSION=v0.6.0-rc1\n' >> .env
printf 'APP_KEY=base64:%s\n' "$(openssl rand -base64 32)" >> .env
docker compose pull
docker compose up -d
docker compose logs -f --tail=100
```

If `.env` already contains `VERSION=...` or `APP_KEY=...`, edit those lines instead of appending duplicates. Do not rotate `APP_KEY` on an existing install unless that is intentional.

## Rollback

Rollback to the previous default published image:

```bash
cd /home/serhii/docker/linguacafe
sed -i '/^VERSION=/d' .env
docker compose pull
docker compose up -d
docker compose logs -f --tail=100
```

Rollback to a specific prior image:

```bash
cd /home/serhii/docker/linguacafe
sed -i 's/^VERSION=.*/VERSION=v0.5.48/' .env
docker compose pull
docker compose up -d
docker compose logs -f --tail=100
```

## Verification Performed

Local images built successfully:

- `ghcr.io/kscop-n1/linguacafe-webserver:local-rc-smoke`
- `ghcr.io/kscop-n1/linguacafe-python-service:local-rc-smoke`

An isolated smoke stack was started with temporary bind mounts under `/tmp/linguacafe-rc-smoke-final`, port `8182` for HTTP, and port `6182` for Reverb.

Checks passed:

```bash
docker compose -f /tmp/linguacafe-rc-smoke-compose.yml ps
docker exec linguacafe-rc-smoke-webserver test -s /var/www/html/public/build/manifest.json
docker exec linguacafe-rc-smoke-webserver php -r 'require "/var/www/html/vendor/autoload.php"; echo "autoload-ok\n";'
docker exec linguacafe-rc-smoke-webserver php -r 'foreach (["mysql"=>3306,"redis"=>6379,"python"=>8678] as $host => $port) { $fp = @fsockopen($host, $port, $errno, $errstr, 2); echo $host . ":" . ($fp ? "ok" : "fail:$errstr") . PHP_EOL; if ($fp) fclose($fp); }'
curl -I http://127.0.0.1:8182/login
agent-browser open http://127.0.0.1:8182/login
agent-browser snapshot -i
```

Observed results:

- All four containers were running; MySQL was healthy.
- Vite `manifest.json` existed in the webserver image.
- Composer autoload loaded successfully.
- Webserver-to-MySQL, Redis, and Python sockets returned `ok`.
- `/login` returned `HTTP/1.1 200 OK`.
- Browser smoke rendered the LinguaCafe login page with `Create first user`, `E-mail address`, `Password`, and disabled `Login` controls.
- Webserver logs showed fresh storage creation, migrations, seeders, and successful supervisord startup for Apache, backup, Horizon, and Reverb.

## Remaining Release Notes

- The GHCR `v0.6.0-rc1` images have not been published by this local audit. Push the tag and run `build-and-push.yml` before asking external installs to pull the RC.
- The local checkout has a `.env` that influences `docker compose config`; deployment verification should be performed from the actual deployment directory before production rollout.
- The Docker audit did not run production data cleanup, migration repair, phrase repair, or the broader UI regression suite by design.
