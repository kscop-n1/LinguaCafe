# Production Maintenance Dry-Run Report

Date: 2026-06-20

Scope:

- invalid vocabulary cleanup;
- vocabulary metadata backfill;
- duplicate and ambiguous legacy word-text review.

No cleanup or backfill apply mode was run.

## Environment Safety

- Repository branch: `main`.
- Coding checkout: `/data/git/LinguaCafe`.
- Live production stack: `/home/serhii/docker/linguacafe`.
- Live production database target: container `linguacafe-database`, database `linguacafe`.
- Live production data was not queried or mutated by the maintenance commands.
- Production-like source: `/home/serhii/docker/linguacafe/storage/backup/linguacafe_2026_06_19_23_59_50.sql`.
- Backup timestamp: 2026-06-19 23:59:50.
- Backup size: 625,732,086 bytes.
- Backup SHA-256: `8e6f297ed88b54ac84a15a6ea041377b654fc4be9480a075bff245167c69cc20`.
- Backup retention: daily backups were present through June 19 plus earlier pre-repair/pre-restore backups.
- Runtime: the backup was restored into ephemeral container `linguacafe-maintenance-mysql` on an isolated Docker network. Current application code was mounted into disposable command containers and pointed only at that disposable database.
- Logging: `LOG_CHANNEL=stderr` prevented command logs from being written into production storage.

The live production image contains an older cleanup command and no metadata-backfill command. It was not used for candidate generation because its output and behavior do not match the reconciled REG-001 implementation.

### Dry-run guarantees

`linguacafe:cleanup-non-word-vocabulary`:

- default mode is dry-run;
- apply option is `--apply`;
- database transactions, chapter saves, encountered-word delete/update operations, and book recalculation are reached only when `--apply` is present;
- dry-run performs SELECT queries and emits one summary log entry;
- no transaction is opened in dry-run mode.

`linguacafe:backfill-vocabulary-metadata`:

- default mode is dry-run;
- apply option is `--apply`;
- row locks, transactions, chapter metadata assignment, and saves are reached only when `--apply` is present;
- dry-run performs SELECT queries and emits one summary log entry;
- no transaction is opened in dry-run mode.

Both commands accept optional `--user-id`, `--language`, and `--chunk` options.

## Exact Commands

Logical dry-run invocations:

```bash
php artisan linguacafe:cleanup-non-word-vocabulary
php artisan linguacafe:backfill-vocabulary-metadata
```

The commands were executed in disposable containers with:

- `DB_HOST=linguacafe-maintenance-mysql`;
- `DB_DATABASE=linguacafe`;
- an ephemeral database credential;
- `LOG_CHANNEL=stderr`;
- no `--apply` option.

Output format:

- pretty-printed JSON on stdout;
- a final human-readable dry-run warning;
- one summary log entry excluding detailed candidate arrays.

## Snapshot Baseline

| Table | Rows | Pre/post checksum |
| --- | ---: | ---: |
| `encountered_words` | 48,562 | 2,756,932,254 |
| `chapters` | 1,972 | 1,016,651,869 |
| `books` | 4 | 3,511,104,817 |
| `phrases` | 16 | 3,956,298,798 |
| `example_sentences` | 293 | 2,880,019,782 |

Pre-run and post-run row counts and checksums were identical.

Snapshot scope:

- user 1, English: 24,281 encountered words and 986 processed chapters;
- user 3, English: 24,281 encountered words and 986 processed chapters.

## Invalid Vocabulary Cleanup Dry-Run

Summary:

| Metric | Count |
| --- | ---: |
| Encountered words scanned | 48,562 |
| Invalid records | 2,398 |
| Safe-delete candidates by current mechanical policy | 449 |
| Would quarantine | 0 |
| Already ignored / no further action | 1,509 |
| Ambiguous / manual review | 440 |
| Deleted | 0 |
| Quarantined | 0 |
| Chapters repaired | 0 |
| Books recalculated | 0 |

The 2,398 records are split evenly between two users:

| User/language scope | Invalid | Safe-delete | Manual review | Already ignored |
| --- | ---: | ---: | ---: | ---: |
| User 1 / English | 1,199 | 223 | 220 | 756 |
| User 3 / English | 1,199 | 226 | 220 | 753 |

### Candidates by reason and action

| Reason | Total | Safe-delete | Manual review | No action | Aggregate chapter refs | Aggregate book refs |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| `arithmetic_expression` | 76 | 76 | 0 | 0 | 0 | 0 |
| `configured_skip_token` | 56 | 0 | 0 | 56 | 12,212 | 82 |
| `dice_notation` | 90 | 4 | 0 | 86 | 40 | 6 |
| `dice_stat_arithmetic` | 32 | 14 | 0 | 18 | 0 | 0 |
| `leading_punctuation_fragment` | 18 | 10 | 0 | 8 | 332 | 16 |
| `number_letter_mixture` | 80 | 80 | 0 | 0 | 22 | 10 |
| `number_only` | 1,094 | 0 | 0 | 1,094 | 2,734 | 764 |
| `punctuation_only` | 20 | 0 | 0 | 20 | 1,518 | 12 |
| `signed_number` | 44 | 0 | 0 | 44 | 38 | 24 |
| `slash_star_math_expression` | 144 | 19 | 0 | 125 | 8 | 8 |
| `standalone_apostrophe_suffix` | 4 | 0 | 0 | 4 | 1,820 | 8 |
| `trailing_punctuation_fragment` | 300 | 246 | 0 | 54 | 80 | 64 |
| `unknown_suspicious_token` | 440 | 0 | 440 | 0 | 176 | 136 |

Chapter/book references are aggregate candidate references and can repeat. Distinct scope:

- all invalid candidates touch 1,972 chapters across 4 user/book scopes;
- safe-delete candidates touch 174 chapters across all 4 user/book scopes;
- manual-review candidates touch 100 chapters across all 4 user/book scopes;
- already-ignored candidates touch all 1,972 chapters.

Association counts:

- encountered-word/vocabulary records: 2,398;
- review/SRS records: 0;
- highlighted records: 0;
- example-sentence records: 0;
- flashcard records: 0 because the legacy flashcard tables no longer exist.

### Representative candidates

Clearly invalid safe-delete examples:

- `+1d`;
- `1d4+poison`;
- `1d10+db`;
- `-fis`;
- `/12*mp`;
- `1d6/`.

Mechanically safe but semantically risky examples:

- `1930s`;
- `10th`;
- `26-year`;
- `1-minute`;
- `t.`;
- `a.k.a.`;
- URLs such as `darringtonpress.com/candela`.

The command's `safe_to_delete` value means only that the row is pristine and unambiguous under the current classifier. It does not prove that every token is linguistically disposable. This distinction blocks blanket apply.

Manual-review examples classified as `unknown_suspicious_token` include:

- `3:30pm`;
- `a.m`;
- `c.1702`;
- `17—and`;
- `antenna—“which`;
- `besides”—she`;
- `genre.ted`;
- `www.chaosium.com`.

No quarantine action is currently proposed because history-bearing invalid records in this snapshot are already at ignored stage. Apply mode would leave those 1,509 records unchanged.

## Metadata Backfill Dry-Run

Command summary:

| Metric | Count |
| --- | ---: |
| Processed chapters scanned | 1,972 |
| Chapters that would change | 1,972 |
| Chapters already correct | 0 |
| Word-only changes | 1,960 |
| Word and phrase changes | 12 |
| Phrase-only changes | 0 |
| Word IDs added | 0 |
| Word IDs removed | 18,980 |
| Phrase IDs added | 0 |
| Phrase IDs removed | 16 |
| Ambiguous chapters | 0 |
| Unresolved words | 0 |
| Chapters changed | 0 |

Per user/book scope:

| User | Book | Chapters | Chapter range | Word IDs removed | Phrase IDs removed |
| ---: | ---: | ---: | --- | ---: | ---: |
| 1 | 2 | 830 | 347-1176 | 7,006 | 0 |
| 1 | 3 | 156 | 1177-1332 | 2,484 | 0 |
| 3 | 4 | 830 | 1333-2162 | 7,006 | 16 |
| 3 | 5 | 156 | 2163-2318 | 2,484 | 0 |

The 18,980 proposed word-ID removals match the aggregate chapter references reported for invalid cleanup candidates. The backfill is therefore primarily proposing removal of IDs whose text is rejected by the current classifier, not filling missing word IDs.

### Phrase ownership issue

The 16 phrase removals occur in 12 user 3 / book 4 chapters:

`1333`, `1335`, `1500`, `1501`, `1541`, `1544`, `1601`, `1602`, `1612`, `1781`, `1908`, `1909`.

Those chapter metadata entries reference phrase IDs `1-6`, owned by user 1. Equivalent same-text phrases exist for user 3 as IDs `7-12`. The current backfill correctly rejects wrong-user IDs but does not map them to the same-text user-scoped replacements. Applying it now would remove phrase associations instead of repairing them.

## Duplicate and Ambiguity Review

- Duplicate groups within the required `(user_id, language, normalized word)` scope: 0.
- Duplicate rows within that scope: 0.
- Backfill-reported duplicate-text ambiguities: 0.
- Backfill unresolved words: 0.

The same token text frequently exists once for user 1 and once for user 3. This is expected user-scoped data, not a duplicate ambiguity. It confirms why backfill must continue enforcing user and language scope.

Cleanup still reports 440 ambiguous records, but these are classifier-level `unknown_suspicious_token` cases rather than duplicate database rows. They are correctly excluded from automatic action.

## Recommendation

### Cleanup apply: not recommended

Do not run the current cleanup command with `--apply` over the full production dataset.

Reasons:

- 449 mechanically safe rows include lexical or document tokens that may be intentionally useful;
- the current command cannot apply an allowlist by rejection reason or token;
- all 1,972 chapters contain metadata references that would be affected across cleanup/backfill processing;
- 440 suspicious tokens require manual review.

Recommended prerequisite: establish an approved token policy and add a scoped allowlist/filter mechanism before considering apply.

### Backfill apply: not recommended

Do not run the current metadata backfill with `--apply`.

Reasons:

- it would change every processed chapter;
- it proposes only removals and no additions;
- 16 wrong-user phrase references have valid same-text user-scoped replacements, but the command would remove rather than remap them;
- word-ID removal should be coordinated with an approved cleanup policy.

Recommended prerequisite: add and test a user/language-scoped phrase-text remap for wrong-owner legacy IDs, then repeat the snapshot dry-run.

## Backup and Rollback Requirements

Before any future apply approval:

1. Create a fresh full production SQL backup immediately before maintenance.
2. Record its timestamp, size, SHA-256, and successful test restore.
3. Export targeted copies of `encountered_words`, `chapters`, `books`, `phrases`, and `example_sentences` for faster selective analysis.
4. Pause imports, readers, review updates, queues, and scheduled jobs during apply.
5. Run dry-run again against the fresh backup and compare candidate counts with this report.
6. Apply one user/language scope at a time using `--user-id` and `--language`.
7. Record pre/post row counts, checksums, chapter totals, book totals, and representative Vocabulary/Reader checks.
8. Roll back by restoring the full verified backup if any metadata, review state, chapter totals, or book totals diverge unexpectedly.

## Verification and Safety Result

- No `--apply` invocation was executed.
- Live production database was not used by either maintenance command.
- Isolated snapshot pre/post row counts matched.
- Isolated snapshot pre/post checksums matched for every potentially affected table.
- No production source code was changed.
- Package lock was not changed by this task.
- Recommendation: retain the high-severity maintenance risk as deferred; do not approve apply yet.

## Remaining Questions

- Should numeric lexical forms such as ordinals, decades, measurements, and numeric hyphen compounds ever be vocabulary?
- Should URLs and domain names be retained, ignored, or deleted?
- Which `unknown_suspicious_token` patterns should become explicit classifier reasons?
- Should already-ignored invalid rows remain for history/audit purposes or be eligible for later archival?
- Should wrong-owner phrase IDs be remapped by same normalized phrase text when exactly one user/language candidate exists?
