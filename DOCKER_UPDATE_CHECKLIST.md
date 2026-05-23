# Docker Update Safety Checklist

Use this checklist before publishing or installing a new LinguaCafe image over an existing installation.

## Existing Installation Must Keep These Paths

Do not delete or replace these persistent directories in the install folder:

| Path | Purpose |
|---|---|
| `./database` | MySQL data directory with users, books, vocabulary, SRS state, and settings |
| `./storage` | Laravel storage, backups, imports, generated files, and user assets |

## Safe Update Steps

1. Backup the current install folder or at least `database/` and `storage/`.
2. Confirm `docker-compose.yml` still mounts the same local `./database` and `./storage` paths.
3. Pull or build the new image.
4. Start the updated stack without deleting volumes or the install directory.
5. Run Laravel migrations in the webserver container.
6. Log in with an existing user.
7. Open an existing book and chapter.
8. Confirm vocabulary entries and reading progress are still present.
9. Confirm review queue opens and at least one due review can be answered.
10. Confirm backup/restore page still lists existing backups from `storage/backup`.

## Commands For A Typical Local Install

```bash
cd /path/to/your/linguacafe-install
docker compose pull
docker compose up -d
docker exec linguacafe-webserver php artisan migrate --force
```

If using a custom fork image, update the image reference in `docker-compose.yml` first, then run the same steps.

## Failure Conditions

Stop the update and restore from backup if any of these happen:

| Check | Failure Signal |
|---|---|
| Login | Existing password no longer works for an existing user |
| Database | New empty install screen appears instead of current data |
| Books | Existing library is empty |
| Vocabulary | Existing saved words are missing |
| Storage | Existing backups are not visible |

## Notes

- Changing the GitHub source or Docker image should not reset users by itself.
- User loss usually means the container started with a different or empty database path.
- A new `APP_KEY` can break encrypted values, but normal Laravel password hashes should still verify if the same database is used.
