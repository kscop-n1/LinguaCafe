# Wrong-Owner Phrase Repair Apply Approval Packet

Date: 2026-06-21

Status: Pending explicit human approval. No apply operation has been run.

Evidence:

- `release-notes/production-maintenance-fresh-dry-run-report-2026-06-21.md`
- `release-notes/vocabulary-cleanup-and-phrase-repair-plan-2026-06-21.md`
- `release-notes/production-maintenance-dry-run-report-2026-06-20.md`
- `release-notes/regression-stabilization-summary-2026-06-20.md`

This packet defines one narrowly scoped future operation. It is not permission to execute it.

## Proposed Apply Scope

The only command proposed for future approval is:

```bash
php artisan linguacafe:repair-wrong-owner-phrase-metadata \
  --user-id=3 \
  --language=english \
  --book-id=4 \
  --apply
```

This command was documented but not executed while preparing this packet.

Scope invariants:

- chapter owner: user `3`;
- language: `english`;
- book: `4`;
- source phrase owner: user `1`;
- replacement phrase owner: user `3`;
- replacement requires exactly one normalized same-text phrase in the chapter owner's user/language scope.

## Candidate Summary

Latest approved evidence source: the isolated restore of
`/home/serhii/docker/linguacafe/storage/backup/linguacafe_2026_06_20_23_59_10.sql`.

| Metric | Expected |
| --- | ---: |
| Chapters scanned in scoped dry-run | 830 |
| Chapters with wrong-owner phrase IDs | 12 |
| Chapters that would change | 12 |
| Unique `unique_phrase_ids` references to remap | 16 |
| Embedded processed-text occurrences to remap | 38 |
| Missing source phrases | 0 |
| No scoped replacement cases | 0 |
| Multiple scoped replacement cases | 0 |
| Unresolved cases | 0 |
| Ambiguous cases | 0 |

The latest dry-run produced exactly one replacement candidate for every source phrase. Source and replacement phrase text matched exactly after ordered trim/lowercase/NFC normalization.

## Full Candidate Table

`Both` means the old ID occurs in the chapter's `unique_phrase_ids` and embedded processed-text `phrase_ids`.

| Chapter | Book | Chapter user | Language | Old phrase ID | Old owner | Old phrase text | Replacement ID | Replacement owner | Replacement text | Metadata location | Embedded occurrences |
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

Approval is invalid if a fresh dry-run differs from any row, owner, text, location, occurrence count, or aggregate above.

## Safety Checklist Before Apply

All items must be completed immediately before any real apply:

1. Create a fresh full production SQL backup.
2. Record the backup path, timestamp, size, and SHA-256.
3. Test-restore that backup into an isolated disposable database.
4. Run the same scoped command without `--apply` against the fresh restore:

   ```bash
   php artisan linguacafe:repair-wrong-owner-phrase-metadata \
     --user-id=3 \
     --language=english \
     --book-id=4
   ```

5. Confirm the final dry-run still reports exactly:
   - 830 chapters scanned;
   - 12 chapters that would change;
   - 16 unique phrase-ID references to remap;
   - 38 embedded processed-text occurrences to remap.
6. Confirm zero unresolved, zero ambiguous, zero missing-source, zero no-scoped-replacement, and zero multiple-scoped-replacement cases.
7. Compare every final candidate row with the full table in this packet.
8. Capture pre-apply row counts and checksums for:
   - `chapters`;
   - `phrases`;
   - `books`;
   - `encountered_words`;
   - `example_sentences`.
9. Export the 12 affected chapter rows, including `unique_phrase_ids` and `processed_text`, for targeted recovery evidence.
10. Pause imports, reading updates, review updates, queues, scheduled jobs, and other application writes for the maintenance window where applicable.
11. Confirm the exact proposed apply command, scope, database target, current code revision, and expected counts.
12. Require explicit human approval after reviewing the final dry-run output and recorded backup evidence.

Any mismatch stops the operation. The packet must be regenerated from the new evidence instead of adjusting the command informally.

## Apply Verification Plan

After a future approved apply, verify all of the following before resuming normal writes:

### Command output

- mode is `apply`;
- `chapters_changed` is exactly `12`;
- `unique_metadata_ids_would_remap` is exactly `16`;
- `processed_text_ids_would_remap` is exactly `38`;
- unresolved and ambiguous counts remain `0`;
- no candidate outside user `3`, language `english`, book `4` appears.

### Database verification

- the 16 old user-1 phrase references are replaced by IDs `7-12` exactly as listed;
- all 38 embedded processed-text occurrences are remapped;
- no unrelated chapter, book, user, or language changes;
- only the 12 approved chapter rows change;
- the `chapters` checksum changes only because of those 12 expected metadata updates;
- the `phrases` row count and checksum remain unchanged;
- `encountered_words` row count and checksum remain unchanged;
- `books` row count and checksum remain unchanged because this command does not recalculate books;
- `example_sentences` row count and checksum remain unchanged;
- user `1` phrase rows and user-1 chapter metadata remain intact.

Capture post-apply row counts/checksums and a targeted before/after diff of all 12 chapter rows.

### Application verification

- Reader opens representative affected chapters `1333`, `1335`, `1601`, and `1909` without phrase mismatch or console/API errors;
- all affected phrase highlights/details resolve to user `3` phrase records;
- Vocabulary phrase filtering for user `3`, book `4` still returns the expected phrases;
- user `1` phrases remain available and unchanged;
- no cross-user phrase data is visible.

Normal application writes may resume only after all checks pass.

## Rollback Plan

Rollback is mandatory if any of these occur:

- final candidate list or count differs from this packet;
- apply output differs from 12 changed chapters, 16 unique remaps, or 38 embedded remaps;
- any unresolved, ambiguous, missing-source, or no-match case appears;
- an unexpected chapter, user, language, book, phrase, or table changes;
- a source/replacement phrase text mismatch is found;
- Reader reports an error or phrase mismatch;
- Vocabulary phrase filtering is incorrect;
- any cross-user phrase leak is observed.

Rollback action:

1. Keep application writes paused.
2. Preserve command output, logs, and pre/post evidence.
3. Restore the fresh verified full SQL backup.
4. Re-run counts, checksums, Reader checks, and Vocabulary phrase checks.
5. Confirm service recovery before resuming writes.

Do not attempt manual SQL patching unless a separate, reviewed recovery plan is written and explicitly approved.

## Explicit Exclusions

This packet does not approve:

- vocabulary cleanup apply;
- broad vocabulary metadata backfill apply;
- deleting or quarantining invalid vocabulary tokens;
- changing tokenizer or classifier policy;
- changing phrase text or phrase ownership;
- any phrase repair outside user `3`, language `english`, book `4`;
- execution of the proposed command without a fresh matching dry-run and separate explicit human approval.

## Approval Record

Current decision: **Not approved for execution**.

Required future record before execution:

```text
Fresh backup:
Backup SHA-256:
Test restore:
Current code revision:
Final dry-run artifact:
Candidate counts match packet: yes/no
All 16 rows reviewed: yes/no
Explicit approver:
Approval timestamp:
Exact approved command:
```

