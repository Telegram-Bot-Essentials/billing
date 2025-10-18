<?php

namespace TelegramBotEssentials\Billing\Events;

use TelegramBotEssentials\Essence\Support\WebhookContext;
use TelegramBotEssentials\Billing\Models\Invoice;

class InvoicePaid extends InvoiceStatusEvent
{
    public function __construct(Invoice $invoice, ?string $previousStatus = null)
    {
        parent::__construct($invoice, $previousStatus);
    }

    public function status(): string
    {
        return 'paid';
    }
}
