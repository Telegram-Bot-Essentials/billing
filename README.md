# Telegram Bot Essentials — Billing

[![Latest Version](https://img.shields.io/packagist/v/telegram-bot-essentials/billing.svg)](https://packagist.org/packages/telegram-bot-essentials/billing)
[![tests](https://github.com/Telegram-Bot-Essentials/billing/actions/workflows/tests.yml/badge.svg)](https://github.com/Telegram-Bot-Essentials/billing/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Invoices, payment attempts, a pluggable gateway registry, and currency conversion on top of
[`telegram-bot-essentials/essence`](https://github.com/Telegram-Bot-Essentials/essence).

Billing does not talk to any payment provider itself — that's what
[`gateway-card`](https://github.com/Telegram-Bot-Essentials/gateway-card) and
[`gateway-zibal`](https://github.com/Telegram-Bot-Essentials/gateway-zibal) are for. It
defines the contract they implement, plus the invoice lifecycle and admin UI everything
else plugs into.

Depends on [`telegram-bot-essentials/settings`](https://github.com/Telegram-Bot-Essentials/settings).

## Installation

```bash
composer require telegram-bot-essentials/billing
php artisan migrate

# optional — customize supported currencies
php artisan vendor:publish --tag=tbe-billing-config
```

## Usage

Make your sellable thing an `Order`:

```php
use TelegramBotEssentials\Billing\Models\Abstract\Order;

class SubscriptionOrder extends Order
{
    public function getPaidAtAttribute(): ?Carbon { /* ... */ }
    public function getAmountAttribute(): string { /* ... */ }
    public function getDescriptionAttribute(): string { /* ... */ }

    public function invoicePaidHook(): void { /* fulfil the order */ }
    public function cancelOrderHook(): void { /* invoice was revoked — undo it */ }
}
```

Create and drive an invoice:

```php
$invoice = billing()->createInvoice($order);
$invoice->markAsPaid();   // fires InvoicePaid → your invoicePaidHook() runs automatically
```

Status transitions always go through `markAsPaid()` / `markAsFailed()` / `markAsPending()`,
never a raw `update()`, so each one fires its event and runs in the correct webhook context
even when triggered from a gateway's server-to-server callback.

Gateway packages register themselves into `gateways()`; the member-facing `InvoiceFeature`
renders a "choose a payment method" screen from whatever is registered. When
[`user-management`](https://github.com/Telegram-Bot-Essentials/user-management) is installed,
Billing adds a per-payable-type revenue breakdown to the user-list header.

`MarkOverdueInvoicesAsFailed` runs hourly — make sure Laravel's scheduler
(`schedule:run` via cron, or `schedule:work`) is running in production.

## Documentation

Full documentation — the `Order` / `HasInvoice` / `PaymentAttempt` contracts, the gateway
registry, currency conversion, and the revenue stats block — lives on the Telegram Bot
Essentials documentation site under **Modules → Billing**.

## License

[MIT](LICENSE).
