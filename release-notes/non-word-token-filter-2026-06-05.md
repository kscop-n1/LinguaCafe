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
- tokens that are not lexical words made of Unicode letters/marks with optional internal apostrophes or hyphens.

Valid words such as `don't`, `it's`, `mother-in-law`, words with diacritics, Cyrillic words, and CJK text remain valid.

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

With `--apply`, the command marks invalid vocabulary rows as ignored (`stage = 1`), clears review scheduling (`next_review = null`, `relearning = false`), and does not hard-delete rows. This removes invalid tokens from vocabulary/search/review/statistics behavior while keeping the operation auditable and repeatable.

The command also removes invalid token references from `chapters.unique_words` and `chapters.unique_word_ids`, recalculates affected chapter `word_count` values from processed text, and recalculates affected book `word_count` values. Already ignored words (`stage = 1`) are counted separately and left as-is.

## Deployment Recommendation

Run dry-run first in production:

```bash
php artisan linguacafe:cleanup-non-word-vocabulary
```

Review the summary and samples, especially `known_to_ignore`, `learning_to_ignore`, `chapters_repaired`, and `books_recalculated`. Then run a scoped apply if the output is expected:

```bash
php artisan linguacafe:cleanup-non-word-vocabulary --user-id=1 --apply
```

Run the unscoped `--apply` only after the dry-run output has been reviewed.
