<?php

namespace TelegramBotEssentials\Billing\Listeners\Invoices;

use TelegramBotEssentials\Billing\Events\InvoiceRevoked;
use TelegramBotEssentials\Billing\Models\Invoice;

class DispatchInvoiceRevokedHooks
{
    public function handle(InvoiceRevoked $event): void
    {
        $invoice = Invoice::find($event->invoice->getKey());

        if (!$invoice) {
            tbeLog('billing')->warning('InvoiceRevoked event skipped because invoice no longer exists.', [
                'invoice_id' => $event->invoice->getKey(),
            ]);
            return;
        }

        try {
            wHook()->api()->sendMessage([
                'chat_id' => $invoice->botUser->telegramUser->peer_id,
                'text' => __('tbe-billing::invoice.hooks.order_reverted'),
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);

            $invoice->messageMeta()->where('tag', 'invoice_view')->get()->each(function ($messageMeta) {
                $messageMeta->lockAction(__('tbe-billing::invoice.locks.user_payment.cancelled'), customEmoji: '❌');
            });
        } catch (\Exception $e) {
            tbeLog('billing')->error('Failed to send InvoiceRevoked notification: ' . $e->getMessage(), ['exception' => $e, 'invoice_id' => $invoice->getKey()]);
        }
    }
}
