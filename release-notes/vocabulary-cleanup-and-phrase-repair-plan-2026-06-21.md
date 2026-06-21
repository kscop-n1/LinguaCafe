# Vocabulary Cleanup Policy and Phrase Repair Plan

Date: 2026-06-21

Related evidence:

- `release-notes/production-maintenance-dry-run-report-2026-06-20.md`
- `release-notes/regression-reconciliation-tracker-2026-06-17.md`
- `release-notes/regression-stabilization-summary-2026-06-20.md`

No production cleanup or backfill apply is approved by this plan.

## Cleanup Lexical Policy

The cleanup command currently distinguishes row safety from classifier validity. That is necessary but insufficient: a pristine row can still contain a useful lexical form. Future apply approval must require both:

1. a row is safe to mutate based on history and associations; and
2. its token category is explicitly approved for that action.

### Proposed category decisions

| Category | Examples | Recommendation | Classifier/report change |
| --- | --- | --- | --- |
| Decades | `1930s` | Keep as vocabulary | Add an explicit valid `decade_expression` rule. Do not clean existing rows. |
| Ordinals | `10th` | Keep as vocabulary | Add an explicit valid `ordinal_expression` rule for supported language patterns. Do not clean existing rows. |
| Numeric hyphen compounds | `26-year`, `1-minute` | Manual review only | Add `numeric_hyphen_compound`. These can be lexical modifiers or parser fragments; do not auto-delete without surrounding-text evidence. |
| Abbreviations | `t.`, `a.k.a.` | Keep known abbreviations; manual review unknown abbreviations | Add `recognized_abbreviation` and `unknown_abbreviation`. Maintain a language-aware allowlist for recognized forms. |
| URLs and domains | `darringtonpress.com/candela` | Delete if pristine; quarantine if history exists | Add `url_or_domain`. They are useful source text but not vocabulary entries. |
| Time expressions | `3:30pm` | Delete if pristine; quarantine if history exists | Add `time_expression`. |
| Historical/date notation | `c.1702` | Manual review only | Add `date_or_historical_notation`; this may encode meaningful text rather than a tokenization failure. |
| Punctuation-joined fragments | `17—and`, `antenna—“which`, `besides”—she` | Delete if pristine; quarantine if history exists | Add `punctuation_joined_fragment`. These are tokenizer-boundary failures and should not survive as one vocabulary item. |
| Dice/stat/math notation | `1d10+db`, `1d4+poison`, `+1d`, `/12*mp` | Delete if pristine; quarantine if history exists | Keep explicit dice/stat/arithmetic reasons. These categories are approved for automatic cleanup once command scoping is implemented. |
| Unknown suspicious tokens | examples currently under `unknown_suspicious_token` | Manual review only | Never mutate automatically. Promote recurring proven patterns into explicit reasons first. |
| Pure punctuation/configured skip tokens | punctuation and tokenizer control fragments | Keep ignored rows unchanged initially; delete only in a separately approved archival batch | Existing rows reference many chapters and are already ignored. Their removal has little user-visible value compared with metadata risk. |

### Policy consequences

- `number_letter_mixture` is too broad for apply because it currently combines valid decades/ordinals with invalid stat notation.
- `arithmetic_expression` is too broad because it currently includes numeric hyphen compounds such as `26-year`.
- `trailing_punctuation_fragment` is too broad because it includes both obvious incomplete dice tokens and abbreviations.
- No apply may select these broad reasons until the classifier splits them into the explicit categories above.
- Existing ignored rows remain non-actionable unless a separate archival policy is approved.

## Safer Cleanup Command Interface

The cleanup command now supports narrow report and guarded apply selection without changing the default dry-run behavior.

### Implemented options

```text
--reason=<reason>              Repeatable allowlist of classifier reasons.
--token=<exact-token>          Repeatable exact token allowlist.
--exclude-token=<token>        Repeatable exact exclusion.
--allow-token=<token>          Treat an exact reviewed token as retained/no-action.
--user-id=<id>                 Existing required ownership scope.
--language=<language>          Existing language scope.
--book-id=<id>                 Restrict through chapter/book associations.
--chapter-id=<id>              Restrict through chapter associations.
--max-candidates=<count>       Hard failure if selected actionable rows exceed the limit.
--report-only-json             Emit JSON only and suppress the human warning/log side channel.
--apply-safe-delete-only       Permit deletion only; never quarantine.
--apply-quarantine-only        Permit quarantine only; never delete.
--apply                        Deprecated compatibility flag; rejected without exactly one explicit action mode.
```

### Implemented apply guards

Any apply invocation fails before opening a transaction unless all conditions hold:

- `--user-id` and `--language` are present;
- at least one positive selector is present: `--reason`, `--token`, `--book-id`, or `--chapter-id`;
- `--max-candidates` is present, non-negative, and the selected actionable encountered-word row count does not exceed it;
- `unknown_suspicious_token`, `numeric_hyphen_compound`, `unknown_abbreviation`, and `date_or_historical_notation` are intrinsically manual-review/no-action categories, and selecting them through `--reason` blocks apply;
- exactly one of `--apply-safe-delete-only` or `--apply-quarantine-only` is selected explicitly;
- plain `--apply`, both action modes, and missing guard inputs return failure without mutation.

Reports include selected user/language/book/chapter scope, positive and negative token selectors, grouped and actionable row counts, delete/quarantine/manual/no-action counts, apply eligibility, and exact ineligibility reasons. `--report-only-json` emits one JSON document without warning or log side-channel output. `--allow-token` keeps a reviewed selected token in the report as `no_action`; it does not make the token actionable.

### Isolated test evidence

`tests/Feature/CleanupNonWordVocabularyTest.php` covers:

- unchanged non-mutating default dry-run;
- reason, exact token, exclusion, reviewed allow-token, book, and chapter filtering;
- rejection for missing user, language, positive selector, or candidate ceiling;
- rejection when actionable rows exceed the ceiling;
- rejection of plain apply and simultaneous action modes;
- delete-only mutation of pristine rows without quarantining history rows;
- quarantine-only mutation of history rows without deleting pristine rows;
- reviewed unsafe `--allow-token` values can remain no-action while another explicitly selected safe token is processed;
- automatic refusal of manual-review/unsafe reasons;
- existing metadata repair and idempotency behavior under the guarded interface.

Focused result: `22 tests, 105 assertions`.

Recommended first cleanup batch after implementation:

- one user and language;
- explicit reasons limited to dice/stat/arithmetic and punctuation-joined fragments;
- safe-delete only;
- a small candidate ceiling;
- fresh production-backup dry-run and human token review before approval.

## Wrong-Owner Phrase Metadata Repair

A dedicated command is implemented:

```bash
php artisan linguacafe:repair-wrong-owner-phrase-metadata
```

Options:

```text
--apply
--user-id=<id>
--language=<language>
--book-id=<id>
--chapter-id=<id>
--chunk=<count>
```

Default behavior is dry-run. No production invocation was performed.

### Matching contract

For each phrase ID found in `chapters.unique_phrase_ids` or embedded processed-text `phrase_ids`:

1. Keep it unchanged when phrase owner and language already match the chapter.
2. If the source phrase is missing, report `missing_source_phrase`.
3. Normalize the source phrase as an ordered JSON array of trimmed, lowercased, NFC-normalized words.
4. Search only phrases owned by the chapter user and in the chapter language.
5. Remap only when exactly one phrase has the same normalized word array.
6. Report `no_scoped_replacement` when none exist.
7. Report `multiple_scoped_phrase_matches` when more than one exists.
8. Preserve unresolved or ambiguous IDs; never remove them.

Explicit apply recomputes the repair under a chapter row lock and updates both:

- `unique_phrase_ids`;
- embedded processed-text `phrase_ids`.

When a remappable wrong-owner ID exists only in processed text, the scoped replacement is also added to `unique_phrase_ids` so both metadata representations remain consistent.

This dedicated command does not run the broad word-ID backfill and cannot remove the 18,980 word IDs reported by the previous dry-run.

## Test Coverage

`tests/Feature/BackfillVocabularyMetadataTest.php` now covers:

- dry-run reports one unique same-text replacement and does not mutate;
- apply remaps both unique and processed-text metadata in the isolated test database;
- processed-text-only remaps add the replacement to `unique_phrase_ids`;
- wrong-owner phrase with no scoped replacement remains unchanged and is unresolved;
- multiple same-text scoped replacements remain unchanged and are ambiguous;
- correct-owner phrase IDs remain unchanged and absent from repair candidates;
- user and language scope prevent cross-user or cross-language remapping;
- book and chapter options restrict apply scope.

Focused result: `8 tests, 47 assertions`.

## Current Apply Decision

Cleanup apply: blocked.

- The classifier reasons are still too broad for mixed lexical categories.
- Command-level positive selectors and apply guards are implemented and tested.
- This code change does not approve a real apply. A fresh production-backup dry-run using the exact intended selectors is still required, followed by human review and explicit approval of the command and candidate count.

Broad metadata backfill apply: blocked.

- The previous command proposes changing all chapters and removing word IDs.
- It must not be used as a substitute for wrong-owner phrase repair.

Wrong-owner phrase repair apply: blocked for production.

- The implementation is tested only against isolated fixtures.
- It must first be run in default dry-run mode against a fresh production backup.
- Every proposed remap must match the expected source/replacement pairs and report zero unresolved or ambiguous cases for the selected apply scope.

## Future Apply Approval Criteria

Before any production apply:

1. Create and test-restore a fresh full backup.
2. Run the current code against that isolated backup.
3. For cleanup, use the new positive selectors, explicit action mode, and candidate ceiling.
4. For phrase repair, run one user/language/book scope at a time.
5. Require zero ambiguous and zero unresolved phrase candidates in the selected scope.
6. Review every source/replacement phrase pair.
7. Confirm no cross-user or cross-language replacement.
8. Record pre/post checksums and targeted chapter metadata.
9. Verify Vocabulary filtering, Reader phrase behavior, chapter statistics, and Review after isolated apply.
10. Obtain explicit human approval for the exact command and candidate count.
