# LinguaCafe Migration Priority Map

Date: 2026-05-24
Source: [migration-audit.md](./migration-audit.md)

## Critical
- 1 Theme source split and auto/live theme behavior. Findings 1, 2, 10, 12, 13, 18, 20.
- 2 Password regression and auth flow. Findings 4, 15, 21.
- 3 Runtime/deployment mismatch. Findings 17, 21.

## High
- 4 Legacy component shims and mixed-era UI contracts. Findings 3, 6, 9, 14, 16, 19.
- 5 Theme settings and text styling contract drift. Findings 5, 12, 18, 19, 20.

## Medium
- 6 Docs/code mismatch and alternate Vue 3 track drift. Findings 6, 7, 8.
- 7 Browser/runtime hygiene cleanup. Findings 17, 19.

## Notes
- The priority groups are intentionally opinionated and favor user-facing breakage first.
- Use [migration-action-plan.md](./migration-action-plan.md) for execution order and verification gates.
