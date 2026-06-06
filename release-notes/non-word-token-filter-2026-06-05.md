# Non-Word Token Filter - 2026-06-05

## Future Import Classifier

`TextBlockService::isVocabularyToken($token, $language)` is the central classifier for vocabulary tokens.

The classifier rejects:

- configured `words_to_skip` tokens;
- numeric-only tokens;
- dice/stat tokens such as `+1d20`, `+10d6`, and `+5d6`;
- slash stat tokens such as `+2/+4`;
- punctuation/emoticon fragments such as `#` and `):`;
- possessive fragments such as `'s`;
- any token without Unicode letters or marks.

Valid words such as contractions, hyphenated words, Cyrillic words, and CJK text remain valid.

Future imports now apply the classifier to:

- word counts;
- chapter unique-word collection;
- creation of new `encountered_words` rows.

Invalid tokens remain renderable in processed text, but they no longer become vocabulary rows.

## Existing Data Cleanup

Existing bad vocabulary rows are not deleted automatically. Use the one-time command:

```bash
php artisan linguacafe:cleanup-non-word-vocabulary
php artisan linguacafe:cleanup-non-word-vocabulary --user-id=1
php artisan linguacafe:cleanup-non-word-vocabulary --language=english
php artisan linguacafe:cleanup-non-word-vocabulary --apply
php artisan linguacafe:cleanup-non-word-vocabulary --user-id=1 --apply
```

The command is dry-run by default. It prints counts and samples before changing anything.

With `--apply`, the command only marks safe invalid stage-2 tokens as ignored (`stage = 1`). It does not hard-delete rows.

Known words (`stage = 0`) and learning/SRS words (`stage < 0`) are reported but skipped. Already ignored words (`stage = 1`) are counted separately and left as-is.

## Deployment Recommendation

Run dry-run first in production:

```bash
php artisan linguacafe:cleanup-non-word-vocabulary
```

Review the summary and samples. Then run a scoped apply if the output is expected:

```bash
php artisan linguacafe:cleanup-non-word-vocabulary --user-id=1 --apply
```

Run the unscoped `--apply` only after the dry-run output has been reviewed.
