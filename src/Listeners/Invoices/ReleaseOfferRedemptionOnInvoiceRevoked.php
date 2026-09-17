<?php

namespace TelegramBotEssentials\Billing\Listeners\Invoices;

use TelegramBotEssentials\Billing\Events\InvoiceRevoked;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Services\OfferService;

class ReleaseOfferRedemptionOnInvoiceRevoked
{
    public function __construct(private OfferService $offerService) {}

    public function handle(InvoiceRevoked $event): void
    {
        $invoice = Invoice::find($event->invoice->getKey());

        if (! $invoice) {
            return;
        }

        $this->offerService->releaseRedemption($invoice);
    }
}
