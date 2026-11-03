<?php

namespace TelegramBotEssentials\Billing\Listeners\Invoices;

use TelegramBotEssentials\Billing\Events\InvoicePaid;
use TelegramBotEssentials\Billing\Models\Invoice;

class DispatchInvoicePaidHooks
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = Invoice::find($event->invoice->getKey());

        if (! $invoice) {
            tbeLog('billing')->warning('InvoicePaid skipped: invoice #{invoice_id} no longer exists', [
                'invoice_id' => $event->invoice->getKey(),
            ]);

            return;
        }

        // Its own task: however Telegram is doing, delivering the order goes on.
        background('tell member invoice #'.$invoice->getKey().' is paid', static function () use ($invoice) {
            wHook()->api()->sendMessage([
                'chat_id' => wHook()->user()->telegramUser->peer_id,
                'text' => __('tbe-billing::invoice.hooks.status_changed.paid'),
                'reply_markup' => replyKeyboard(),
            ]);

            $invoice->messageMeta()->where('tag', 'invoice_view')->get()->each(function ($messageMeta) {
                $messageMeta->lockAction(__('tbe-billing::invoice.locks.user_payment.accepted'), customEmoji: '✅');
            });
        }, for: $invoice->botUser, tries: 3, backoff: [10, 60]);
    }
}
