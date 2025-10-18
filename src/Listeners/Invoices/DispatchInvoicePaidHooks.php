<?php

namespace TelegramBotEssentials\Billing\Listeners\Invoices;

use Illuminate\Support\Facades\Log;
use TelegramBotEssentials\Billing\Events\InvoicePaid;
use TelegramBotEssentials\Billing\Jobs\InvoicePaidHookJob;
use TelegramBotEssentials\Billing\Models\Invoice;

class DispatchInvoicePaidHooks
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = Invoice::find($event->invoice->getKey());

        if (!$invoice) {
            Log::warning('InvoicePaid event skipped because invoice no longer exists.', [
                'invoice_id' => $event->invoice->getKey(),
            ]);
            return;
        }

        $invoice->loadMissing(['bot', 'botUser']);

        $event->context->apply();

        InvoicePaidHookJob::dispatch($invoice);
    }
}
