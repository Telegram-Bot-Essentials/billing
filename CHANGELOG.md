# Changelog

All notable changes to this project are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/). Until the API
stabilizes at 1.0 a `0.0.x` bump may carry breaking changes.

## [Unreleased]

## [0.0.32] - 2026-09-01

### Changed

- **BREAKING:** requires `telegram-bot-essentials/essence` `^0.12`. Handlers
  hold translation keys and resolve them lazily via `__()`, and the invoice
  page prompt resumes through `StateAnswer::requireMessageMeta()` so a resume
  against a pruned `MessageMeta` shows a "step expired" notice instead of
  crashing the worker.

### Added

- Revenue-stats block in the bot-user-list header (when
  `telegram-bot-essentials/user-management` is installed): paid invoices per
  payable type over 24h / 7d / 30d / all time, plus distinct buyers for the
  month. `Order::statsLabel()` lets each order type name itself (0.0.31).
- Pest test suite, Laravel Pint, Larastan (level max), GitHub Actions CI,
  Laravel Workbench, `LICENSE` (MIT) and this changelog.

### Fixed

- The admin invoice-detail screen reads its order description and amount from
  `$invoice->payable` instead of the always-empty
  `$invoice->paymentAttempt->description`, and no longer labels an invoice
  that never had a payment attempt as "Pending" (0.0.31).
- `Currency::multiply()` no longer accepts `float` — `brick/math` 0.18 rejects
  it, since a float can't hold most decimal money values exactly.
