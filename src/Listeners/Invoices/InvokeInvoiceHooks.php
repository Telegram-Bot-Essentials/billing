<?php

namespace TelegramBotEssentials\Billing\Listeners\Invoices;

use TelegramBotEssentials\Billing\Events\InvoiceFailed;
use TelegramBotEssentials\Billing\Events\InvoicePaid;
use TelegramBotEssentials\Billing\Events\InvoicePending;
use TelegramBotEssentials\Billing\Events\InvoiceRevoked;
use TelegramBotEssentials\Billing\Events\InvoiceStatusEvent;
use TelegramBotEssentials\Billing\Models\Invoice;

class InvokeInvoiceHooks
{
    /**
     * @param  InvoiceStatusEvent  $event
     */
    public function handle(InvoiceStatusEvent $event): void
    {
        $invoice = Invoice::find($event->invoice->getKey());

        if (!$invoice) {
            tbeLog('billing')->warning('Invoice hook skipped because invoice no longer exists.', [
                'invoice_id' => $event->invoice->getKey(),
            ]);
            return;
        }

        $method = $this->resolveHookMethod($event);

        if (!$method) {
            return;
        }

        $payable = $invoice->payable;

        if ($payable && method_exists($payable, $method)) {
            $payable->{$method}();
        }
    }

    private function resolveHookMethod(InvoiceStatusEvent $event): ?string
    {
        return match (true) {
            $event instanceof InvoicePaid => 'invoicePaidHook',
            $event instanceof InvoiceFailed => 'invoiceFailedHook',
            $event instanceof InvoicePending => 'invoicePendingHook',
            $event instanceof InvoiceRevoked => 'cancelOrderHook',
            default => null,
        };
    }
}

