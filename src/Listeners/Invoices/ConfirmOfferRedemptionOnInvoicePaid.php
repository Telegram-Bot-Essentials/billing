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

        $this->offerService->confirmRedemption($invoice);
    }
}
