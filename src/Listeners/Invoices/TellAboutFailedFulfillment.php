<?php

namespace TelegramBotEssentials\Billing\Listeners\Invoices;

use TelegramBotEssentials\Billing\Events\InvoiceFulfillmentFailed;

class TellAboutFailedFulfillment
{
    public function handle(InvoiceFulfillmentFailed $event): void
    {
        $invoice = $event->invoice;
        $invoiceId = $invoice->getKey();

        // By order, so a second failure in the window is not throttled away.
        ownerAlert('billing.fulfillment.'.$invoiceId, fn () => __('tbe-billing::invoice.alerts.fulfillment_failed', [
            'invoiceId' => $invoiceId,
            'error' => (string) $invoice->getAttribute('fulfillment_error'),
        ]));

        // After any commit: if the payment rolls back there is nothing to apologise for.
        background('tell member about failed fulfillment', static function () {
            notifyUser(wHook()->user(), __('tbe-billing::invoice.hooks.fulfillment_failed'));
        }, for: $invoice->botUser);
    }
}
