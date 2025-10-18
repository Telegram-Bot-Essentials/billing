<?php

namespace TelegramBotEssentials\Billing\Listeners\Invoices;

use Illuminate\Support\Facades\Log;
use TelegramBotEssentials\Billing\Events\InvoicePending;
use TelegramBotEssentials\Billing\Jobs\InvoicePendingHookJob;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Support\WebhookContext;

class DispatchInvoicePendingHooks
{
    public function handle(InvoicePending $event): void
    {
        $invoice = Invoice::find($event->invoice->getKey());

        if (!$invoice) {
            Log::warning('InvoicePending event skipped because invoice no longer exists.', [
                'invoice_id' => $event->invoice->getKey(),
            ]);
            return;
        }

        $invoice->loadMissing(['bot', 'botUser']);

        $event->context->apply();

        InvoicePendingHookJob::dispatch($invoice);
    }
}
