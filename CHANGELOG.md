# Changelog

All notable changes to this project are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/). Until the API
stabilizes at 1.0 a `0.0.x` bump may carry breaking changes.

## [Unreleased]

## [0.0.34] - 2026-09-20

### Added

- `Order::offersAllowed()`: an order type can opt its invoices out of offer
  codes entirely (defaults `true`). `OfferService::redeem()` enforces it,
  `InvoiceFeature` hides the use-code button on such an invoice, and
  `InvoiceQuery::useOfferCode()` guards the callback too, in case a stale
  `callback_data` reaches it directly.
- `ManageInvoicesFeature::show()` now displays the offer code applied to an
  invoice and the price it replaced.

## [0.0.33] - 2026-09-20

### Added

- Offer codes: an admin manager (list, detail, stats, enable/disable, per-field
  edit) and a creation form built on essence's forms engine. `CreateOfferForm`
  asks the code, type, amount and the optional limits with Back / Next / Skip
  / Finish on the reply keyboard, drops the max-discount cap step for fixed offers,
  and writes nothing until the admin confirms the summary, so an abandoned
  form leaves no half-built offer behind (there is no draft row, no
  `PruneAbandonedOfferDrafts` command, and `offers.type` / `amount` are
  `NOT NULL`).
- `InvoiceFeature::paymentSummary()` and `offerSummary()`: what paying an
  invoice costs, including the offer code applied and the price it replaced,
  for a gateway to show next to its own instructions (the card gateway now
  does).

### Changed

- **BREAKING:** requires `telegram-bot-essentials/essence` `^0.13` (the forms
  engine and the JSON user state).

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
