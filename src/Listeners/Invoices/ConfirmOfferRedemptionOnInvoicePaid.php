<?php

namespace TelegramBotEssentials\Billing\Listeners\Invoices;

use TelegramBotEssentials\Billing\Events\InvoicePaid;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Services\OfferService;

class ConfirmOfferRedemptionOnInvoicePaid
{
    public function __construct(private OfferService $offerService) {}

    public function handle(InvoicePaid $event): void
    {
        $invoice = Invoice::find($event->invoice->getKey());

        if (! $invoice) {
            return;
        }

        // Its own step: a redemption that cannot be recorded must not hold up the order.
        isolated('confirm offer of invoice #'.$invoice->getKey(), fn () => $this->offerService->confirmRedemption($invoice));
    }
}
