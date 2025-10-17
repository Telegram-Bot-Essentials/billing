<?php

namespace TelegramBotEssentials\Billing\Events;

use TelegramBotEssentials\Billing\DTOs\WebhookContext;
use TelegramBotEssentials\Billing\Models\Invoice;

class InvoicePending extends InvoiceStatusEvent
{
    public function __construct(Invoice $invoice, ?string $previousStatus = null, ?WebhookContext $context = null)
    {
        parent::__construct($invoice, $previousStatus, $context);
    }

    public function status(): string
    {
        return 'pending';
    }
}

