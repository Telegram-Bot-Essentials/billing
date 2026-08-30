<?php

namespace TelegramBotEssentials\Billing\Listeners\Invoices;

use TelegramBotEssentials\Billing\Events\InvoicePending;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Telegram\Features\Member\InvoiceFeature;

class DispatchInvoicePendingHooks
{
    public function handle(InvoicePending $event): void
    {
        $invoice = Invoice::find($event->invoice->getKey());

        if (! $invoice) {
            tbeLog('billing')->warning('InvoicePending event skipped because invoice no longer exists.', [
                'invoice_id' => $event->invoice->getKey(),
            ]);

            return;
        }

        try {
            wHook()->api()->sendMessage([
                'chat_id' => $invoice->botUser->telegramUser->peer_id,
                'text' => __('tbe-billing::invoice.hooks.status_changed.pending'),
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);

            $telegramResponse = InvoiceFeature::invoice($invoice);
            $invoice->messageMeta()->where('tag', 'invoice_view')->get()->each(function ($messageMeta) use ($telegramResponse) {
                $messageMeta->updateAndContinueAction($telegramResponse);
            });
        } catch (\Exception $e) {
            tbeLog('billing')->error('Failed to send InvoicePending notification: '.$e->getMessage(), ['exception' => $e, 'invoice_id' => $invoice->getKey()]);
        }
    }
}
