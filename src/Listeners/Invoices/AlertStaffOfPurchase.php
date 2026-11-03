<?php

namespace TelegramBotEssentials\Billing\Listeners\Invoices;

use Brick\Math\BigDecimal;
use TelegramBotEssentials\Billing\Events\InvoicePaid;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Services\PurchaseAlerts;

class AlertStaffOfPurchase
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = Invoice::find($event->invoice->getKey());

        if (! $invoice || ! settings()->get('billing.purchase_alerts.status')) {
            return;
        }

        // Prizes and free plans settle at 0: nobody bought anything.
        if (! BigDecimal::of((string) $invoice->price)->isPositive()) {
            return;
        }

        // Its own task, after any commit: a sale that rolls back is no sale,
        // and an alert must not hold up delivering the order.
        background('alert staff of purchase #'.$invoice->getKey(), static function () use ($invoice) {
            app(PurchaseAlerts::class)->send($invoice);
        });
    }
}
