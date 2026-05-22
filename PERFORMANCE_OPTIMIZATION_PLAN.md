# LinguaCafe Performance Optimization Plan

This document tracks performance work that improves large-book behavior without rewriting the application.

## Status Legend

- `Todo`: Not started.
- `In Progress`: Currently being implemented or verified.
- `Done`: Implemented and verified.
- `Blocked`: Needs a decision, dependency, or larger redesign.

## Progress Tracker

| ID | Area | Priority | Status | Goal | Verification |
|---|---|---:|---|---|---|
| P1 | Reader rendering | High | Done | Progressive rendering avoids creating all word spans in the first Vue render | Production build passes |
| P2 | Reader selection/hover | High | Done | Reset only changed `selected`/`hover` words | Production build passes |
| P3 | Book word counts | High | Done | Avoid loading all user vocabulary for one book count | PHPUnit passes |
| P4a | Vocabulary books/chapters list | High | Done | Remove N+1 chapter queries from vocabulary search setup | PHPUnit passes |
| P4b | Vocabulary chapter metadata | High | Done | Avoid `processed_text` reads for phrase filtering via `unique_phrase_ids` metadata | PHPUnit passes |
| P5a | Review book/chapter filter | Medium | Done | Remove N+1 chapter queries and use `unique_words` metadata for word filtering | PHPUnit passes |
| P5b | Review phrase metadata | Medium | Done | Avoid `processed_text` reads for phrase ids via `unique_phrase_ids` metadata | PHPUnit passes |
| P6 | DB indexes | Medium | Done | Add composite indexes for frequent filters | Migration is present and test DB has nothing pending after PHPUnit |
| P7 | CSV import | Low | Done | Use batch lookup instead of per-row lookup | PHPUnit passes |

## Implementation Notes

- Completed batches: P1, P2, P3, P4a, P4b, P5a, P5b, P6, P7.
- Reader now uses progressive rendering; full virtualization can be considered later if mobile DOM memory is still an issue.
- Prefer changes that keep existing endpoint URLs and response shapes stable.
- Do not change Docker volume paths or database persistence behavior.

## Acceptance Checklist

- [x] `php -l` passes for changed PHP files.
- [x] PHPUnit passes against the Docker test database.
- [x] `npm run production` passes if frontend files change.
- [x] Migration check passes on test DB; existing Docker install should still be backed up before update.
- [ ] Large book library and vocabulary screens remain functional.
