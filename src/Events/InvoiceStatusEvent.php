<?php

namespace TelegramBotEssentials\Billing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TelegramBotEssentials\Billing\DTOs\WebhookContext;
use TelegramBotEssentials\Billing\Models\Invoice;

abstract class InvoiceStatusEvent
{
    use Dispatchable;
    use SerializesModels;

    public Invoice $invoice;

    public function __construct(
        Invoice $invoice,
        public readonly ?string $previousStatus = null,
        public readonly WebhookContext|null $context = null,
    ) {
        $this->invoice = $invoice->withoutRelations();
    }

    abstract public function status(): string;
}

