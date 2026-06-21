# Fresh Production-Backup Maintenance Dry-Run Report

Date: 2026-06-21

Scope:

- dedicated wrong-owner phrase metadata repair;
- guarded invalid-vocabulary cleanup reporting;
- mutation verification against a fresh production backup.

No `--apply` option or apply action mode was used. The live production database was not queried or mutated.

## Environment Safety

- Repository branch: `main`.
- Coding checkout: `/data/git/LinguaCafe`.
- Production stack path: `/home/serhii/docker/linguacafe`.
- Live production database: container `linguacafe-database`, database `linguacafe`, network `linguacafe_linguacafe`.
- Backup source: `/home/serhii/docker/linguacafe/storage/backup/linguacafe_2026_06_20_23_59_10.sql`.
- Backup file timestamp: 2026-06-21 02:59:13 +03:00; the filename records the backup job time as 2026-06-20 23:59:10.
- Backup size: 625,732,169 bytes.
- Backup SHA-256: `8fdd6ffba93dff5e6b23c99d755422ec7a7ca94ac82895ef193f3609593eb01f`.
- Disposable Docker network: `linguacafe-maintenance-20260621`.
- Disposable MySQL container: `linguacafe-maintenance-mysql-20260621`.
- Disposable database: `linguacafe_maintenance_20260621`.
- Database storage: container-only tmpfs at `/var/lib/mysql`.
- Published database ports: none.
- Application runtime: disposable `linguacafedev-webserver:latest` containers with the current checkout mounted read-only.
- Logging: `LOG_CHANNEL=stderr`; application containers had only temporary framework storage.

The disposable database was attached only to `linguacafe-maintenance-20260621`. It was not attached to the production network. The SQL dump contained no `CREATE DATABASE`, `DROP DATABASE`, or `USE` statement and was explicitly restored into `linguacafe_maintenance_20260621`.

Dry-run code-path checks:

- phrase-repair transactions, row locks, metadata assignments, and chapter saves execute only when `--apply` is present;
- cleanup transactions, metadata repair, encountered-word deletes/updates, and book recalculation execute only when an apply action mode is present;
- all application containers mounted the checkout read-only, adding an operating-system-level guard against code/runtime artifact writes;
- matching database checksums below provide the final mutation proof.

## Commands

Logical maintenance invocations:

```bash
php artisan linguacafe:repair-wrong-owner-phrase-metadata
php artisan linguacafe:repair-wrong-owner-phrase-metadata --user-id=3 --language=english
php artisan linguacafe:repair-wrong-owner-phrase-metadata --user-id=3 --language=english --book-id=4

php artisan linguacafe:cleanup-non-word-vocabulary

php artisan linguacafe:cleanup-non-word-vocabulary \
  --user-id=1 --language=english \
  --reason=dice_notation \
  --reason=dice_stat_arithmetic \
  --reason=slash_star_math_expression \
  --max-candidates=50 --report-only-json

php artisan linguacafe:cleanup-non-word-vocabulary \
  --user-id=3 --language=english \
  --reason=dice_notation \
  --reason=dice_stat_arithmetic \
  --reason=slash_star_math_expression \
  --max-candidates=50 --report-only-json

php artisan linguacafe:cleanup-non-word-vocabulary \
  --user-id=1 --language=english \
  --token=1d10+db --token=1d4+poison --token=+1d --token=/12*mp \
  --max-candidates=50 --report-only-json

php artisan linguacafe:cleanup-non-word-vocabulary \
  --user-id=3 --language=english \
  --token=1d10+db --token=1d4+poison --token=+1d --token=/12*mp \
  --max-candidates=50 --report-only-json
```

No command line contained `--apply`, `--apply-safe-delete-only`, or `--apply-quarantine-only`.

## Pre/Post Mutation Check

| Table | Rows before | Checksum before | Rows after | Checksum after | Match |
| --- | ---: | ---: | ---: | ---: | --- |
| `encountered_words` | 48,562 | 2,756,932,254 | 48,562 | 2,756,932,254 | Yes |
| `chapters` | 1,972 | 1,016,651,869 | 1,972 | 1,016,651,869 | Yes |
| `books` | 4 | 3,511,104,817 | 4 | 3,511,104,817 | Yes |
| `phrases` | 16 | 3,956,298,798 | 16 | 3,956,298,798 | Yes |
| `example_sentences` | 293 | 2,880,019,782 | 293 | 2,880,019,782 | Yes |

The pre-run and post-run TSV snapshots had no diff.

## Phrase Repair Dry-Run

### Aggregate result

| Metric | Unscoped | User 3 / English | User 3 / English / Book 4 |
| --- | ---: | ---: | ---: |
| Chapters scanned | 1,972 | 986 | 830 |
| Chapters with wrong-owner IDs | 12 | 12 | 12 |
| Chapters that would change | 12 | 12 | 12 |
| Wrong-owner chapter/phrase pairs | 16 | 16 | 16 |
| Planned unambiguous remaps | 16 | 16 | 16 |
| `unique_phrase_ids` entries to remap | 16 | 16 | 16 |
| Embedded processed-text occurrences to remap | 38 | 38 | 38 |
| Missing source phrases | 0 | 0 | 0 |
| No scoped replacement | 0 | 0 | 0 |
| Multiple scoped replacements | 0 | 0 | 0 |
| Chapters changed | 0 | 0 | 0 |

All 16 planned remaps have exactly one replacement in the chapter owner's user/language scope.

Independent metadata inventory:

- correct-owner chapter/phrase pairs ignored by repair: 23;
- correct-owner processed-text occurrences ignored by repair: 53;
- wrong-owner chapter/phrase pairs: 16;
- missing-source chapter/phrase pairs: 0.

### Proposed remap review

`Both` means the old ID occurs in `unique_phrase_ids` and embedded processed-text `phrase_ids`.

| Chapter | Book | Chapter user | Language | Old ID | Old owner | Old text | New ID | New owner | Replacement text | Location | Embedded occurrences |
| ---: | ---: | ---: | --- | ---: | ---: | --- | ---: | ---: | --- | --- | ---: |
| 1333 | 4 | 3 | `english` | 1 | 1 | `seemed more apt` | 7 | 3 | `seemed more apt` | Both | 3 |
| 1333 | 4 | 3 | `english` | 2 | 1 | `perhaps by mere dozens .` | 8 | 3 | `perhaps by mere dozens .` | Both | 5 |
| 1335 | 4 | 3 | `english` | 3 | 1 | `filing cabinet` | 9 | 3 | `filing cabinet` | Both | 2 |
| 1335 | 4 | 3 | `english` | 4 | 1 | `barest squeak` | 10 | 3 | `barest squeak` | Both | 2 |
| 1335 | 4 | 3 | `english` | 5 | 1 | `puzzling over` | 11 | 3 | `puzzling over` | Both | 2 |
| 1335 | 4 | 3 | `english` | 6 | 1 | `herded beasts` | 12 | 3 | `herded beasts` | Both | 2 |
| 1500 | 4 | 3 | `english` | 3 | 1 | `filing cabinet` | 9 | 3 | `filing cabinet` | Both | 2 |
| 1501 | 4 | 3 | `english` | 3 | 1 | `filing cabinet` | 9 | 3 | `filing cabinet` | Both | 2 |
| 1541 | 4 | 3 | `english` | 3 | 1 | `filing cabinet` | 9 | 3 | `filing cabinet` | Both | 2 |
| 1544 | 4 | 3 | `english` | 3 | 1 | `filing cabinet` | 9 | 3 | `filing cabinet` | Both | 2 |
| 1601 | 4 | 3 | `english` | 3 | 1 | `filing cabinet` | 9 | 3 | `filing cabinet` | Both | 4 |
| 1602 | 4 | 3 | `english` | 3 | 1 | `filing cabinet` | 9 | 3 | `filing cabinet` | Both | 2 |
| 1612 | 4 | 3 | `english` | 5 | 1 | `puzzling over` | 11 | 3 | `puzzling over` | Both | 2 |
| 1781 | 4 | 3 | `english` | 3 | 1 | `filing cabinet` | 9 | 3 | `filing cabinet` | Both | 2 |
| 1908 | 4 | 3 | `english` | 3 | 1 | `filing cabinet` | 9 | 3 | `filing cabinet` | Both | 2 |
| 1909 | 4 | 3 | `english` | 3 | 1 | `filing cabinet` | 9 | 3 | `filing cabinet` | Both | 2 |

The scoped and unscoped reports are consistent. No cross-user or cross-language guess is proposed.

## Cleanup Dry-Run

### Default baseline

| Metric | Count |
| --- | ---: |
| Encountered words scanned | 48,562 |
| Invalid records | 2,398 |
| Selected candidates | 2,398 |
| Actionable safe-delete rows | 449 |
| Quarantine rows | 0 |
| Manual-review rows | 440 |
| Already ignored/no-action rows | 1,509 |
| Deleted | 0 |
| Quarantined | 0 |

The baseline matches the June 20 report. It is not apply-eligible because it has no user/language scope, positive selector, ceiling, or explicit action mode.

### Guarded reason scopes

Selected reasons:

- `dice_notation`;
- `dice_stat_arithmetic`;
- `slash_star_math_expression`.

No token exclusions or reviewed allow-tokens were supplied.

| Scope | Total invalid in scope | Selected | Excluded | Safe delete | Quarantine | Manual review | No action | Apply eligible |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | --- |
| User 1 / English | 1,199 | 133 | 1,066 | 18 | 0 | 0 | 115 | No |
| User 3 / English | 1,199 | 133 | 1,066 | 19 | 0 | 0 | 114 | No |

The actionable counts are below `--max-candidates=50`. The only ineligibility reasons are:

- `no_apply_action_mode_selected`;
- `exactly_one_apply_action_mode_required`.

This is expected for dry-run-only invocations.

Candidate tokens mechanically classified as safe-delete in this reason scope:

| Token | User 1 | User 3 | Notes |
| --- | --- | --- | --- |
| `1d4+poison` | Safe delete | Safe delete | Dice/stat expression |
| `1d10+db` | Safe delete | Safe delete | Dice/stat expression |
| `1d6+db` | Safe delete | Safe delete | Dice/stat expression |
| `1d3+db` | Safe delete | Safe delete | Dice/stat expression |
| `1d8+db` | Safe delete | Safe delete | Dice/stat expression |
| `1d4+db` | Safe delete | Safe delete | Dice/stat expression |
| `2d6+burn` | Safe delete | Safe delete | Dice/stat expression |
| `+1d` | Safe delete | Safe delete | Incomplete dice notation |
| `+2d` | Safe delete | Safe delete | Incomplete dice notation |
| `7/13*mp` | Safe delete | Safe delete | Slash/star stat expression |
| `7/11*mp` | Safe delete | Safe delete | Slash/star stat expression |
| `/12*mp` | No action | Safe delete | User 1 row is already ignored |
| `fist*80` | Safe delete | Safe delete | Stat expression |
| `switchblade*65` | Safe delete | Safe delete | Stat expression |
| `str/5` | Safe delete | Safe delete | Stat expression |
| `http://home.comcast` | Safe delete | Safe delete | URL fragment |
| `net/~pulpgallery` | Safe delete | Safe delete | URL fragment |
| `darringtonpress.com/candela` | Safe delete | Safe delete | URL/domain |
| `https://bit.ly/ttrpgsafetytoolkit` | Safe delete | Safe delete | URL |

The same reason scope also selected 114-115 already ignored/no-action dice and fraction-like rows. Those rows are not future mutation candidates.

Punctuation-joined fragments and time/date-like tokens remain under `unknown_suspicious_token` in this snapshot. They were intentionally not selected because the policy requires manual review and forbids treating that broad reason as actionable.

### Exact reviewed token scopes

| User | Token | Reason | Action | Encountered row | Chapter refs | Book refs |
| ---: | --- | --- | --- | ---: | ---: | ---: |
| 1 | `1d4+poison` | `dice_stat_arithmetic` | Safe delete | 9,137 | 0 | 0 |
| 1 | `1d10+db` | `dice_stat_arithmetic` | Safe delete | 10,991 | 0 | 0 |
| 1 | `/12*mp` | `slash_star_math_expression` | No action; stage 1 | 13,238 | 0 | 0 |
| 1 | `+1d` | `dice_notation` | Safe delete | 22,306 | 12 | Book 3 |
| 3 | `1d4+poison` | `dice_stat_arithmetic` | Safe delete | 33,418 | 0 | 0 |
| 3 | `1d10+db` | `dice_stat_arithmetic` | Safe delete | 35,272 | 0 | 0 |
| 3 | `/12*mp` | `slash_star_math_expression` | Safe delete | 37,519 | 0 | 0 |
| 3 | `+1d` | `dice_notation` | Safe delete | 46,587 | 12 | Book 5 |

Exact-scope totals:

| Scope | Selected | Safe delete | Quarantine | Manual review | No action | Apply eligible |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| User 1 / English | 4 | 3 | 0 | 0 | 1 | No |
| User 3 / English | 4 | 4 | 0 | 0 | 0 | No |

Again, the only ineligibility reasons are the deliberately absent apply action mode. No mutation occurred.

The `+1d` candidates reference 12 chapters each:

- user 1/book 3: chapters `1185`, `1188`, `1198`, `1199`, `1200`, `1201`, `1202`, `1203`, `1212`, `1217`, `1220`, `1320`;
- user 3/book 5: chapters `2171`, `2174`, `2184`, `2185`, `2186`, `2187`, `2188`, `2189`, `2198`, `2203`, `2206`, `2306`.

Any future exact-token apply must therefore review the planned chapter metadata repair, not only the encountered-word deletion.

## Unresolved And Ambiguous Cases

- Phrase repair unresolved: 0.
- Phrase repair ambiguous: 0.
- Phrase repair missing source: 0.
- Cleanup manual-review rows in the broad baseline: 440.
- Cleanup manual-review rows selected by the guarded reason/exact-token reports: 0.
- Broad mixed categories such as `number_letter_mixture`, `arithmetic_expression`, `trailing_punctuation_fragment`, and `unknown_suspicious_token` were not selected.
- Decades, ordinals, abbreviations, numeric-hyphen compounds, historical/date notation, and unknown punctuation-joined/time tokens remain outside any proposed automatic action.

## Recommendation

### Current decision

Apply remains blocked.

This task authorizes no production mutation. It proves only that:

- the dedicated phrase repair identifies 16 exact, user/language-scoped replacements with zero ambiguity;
- guarded cleanup selectors can isolate small reviewed sets;
- dry-runs do not mutate the restored data.

### Scope that may be considered later

Wrong-owner phrase repair may be considered in a separate explicitly approved operation limited to:

```text
user_id=3, language=english, book_id=4
```

Prerequisites remain:

- a new immediate pre-maintenance backup and tested restore;
- review/approval of all 16 rows above;
- an explicit apply command reviewed before execution;
- pre/post chapter metadata capture and Reader verification.

Cleanup may be considered only as exact-token batches, not as blanket or broad mixed-reason apply. The four-token scope above is reviewable, but `+1d` requires chapter metadata verification and user 1 `/12*mp` is already no-action. A future apply proposal should state the exact user, language, tokens, action mode, candidate ceiling, and expected chapter changes.

## Teardown And Repository Safety

After recording post-run checksums:

- the disposable MySQL container and network were removed;
- temporary JSON, TSV, stderr, and analysis files were removed;
- `package-lock.json` remained unchanged;
- no generated build/runtime artifact remained;
- repository changes were limited to this report.

Final `git status --short`:

```text
?? release-notes/production-maintenance-fresh-dry-run-report-2026-06-21.md
```
