<?php

namespace TelegramBotEssentials\Billing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TelegramBotEssentials\Essence\Support\WebhookContext;
use TelegramBotEssentials\Billing\Models\Invoice;

abstract class InvoiceStatusEvent
{
    use Dispatchable;
    use SerializesModels;

    public Invoice $invoice;
    public WebhookContext $context;

    public function __construct(
        Invoice $invoice,
        public readonly ?string $previousStatus = null,
    ) {
        $this->context = WebhookContext::capture();
        $this->invoice = $invoice->withoutRelations();
    }

    abstract public function status(): string;
}
