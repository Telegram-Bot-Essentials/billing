<?php

namespace TelegramBotEssentials\Billing\Listeners\Invoices;

use TelegramBotEssentials\Billing\Events\InvoicePaid;
use TelegramBotEssentials\Billing\Models\Invoice;

class DispatchInvoicePaidHooks
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = Invoice::find($event->invoice->getKey());

        if (!$invoice) {
            tbeLog('billing')->warning('InvoicePaid event skipped because invoice no longer exists.', [
                'invoice_id' => $event->invoice->getKey(),
            ]);
            return;
        }

        try {
            wHook()->api()->sendMessage([
                'chat_id' => $invoice->botUser->telegramUser->peer_id,
                'text' => __('tbe-billing::invoice.hooks.status_changed.paid'),
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);

            $invoice->messageMeta()->where('tag', 'invoice_view')->get()->each(function ($messageMeta) {
                $messageMeta->lockAction(__('tbe-billing::invoice.locks.user_payment.accepted'), customEmoji: "✅");
            });
        } catch (\Exception $e) {
            tbeLog('billing')->error('Failed to send InvoicePaid notification: ' . $e->getMessage(), ['exception' => $e, 'invoice_id' => $invoice->getKey()]);
        }
    }
}
