<?php

namespace TelegramBotEssentials\Billing\Listeners\Invoices;

use Illuminate\Support\Facades\Log;
use TelegramBotEssentials\Billing\Events\InvoiceFailed;
use TelegramBotEssentials\Billing\Models\Invoice;

class DispatchInvoiceFailedHooks
{
    public function handle(InvoiceFailed $event): void
    {
        $invoice = Invoice::find($event->invoice->getKey());

        if (!$invoice) {
            Log::warning('InvoiceFailed event skipped because invoice no longer exists.', [
                'invoice_id' => $event->invoice->getKey(),
            ]);
            return;
        }

        try {
            wHook()->api()->sendMessage([
                'chat_id' => $invoice->botUser->telegramUser->peer_id,
                'text' => __('tbe-billing::invoice.hooks.status_changed.failed'),
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);

            $invoice->messageMeta()->where('tag', 'invoice_view')->get()->each(function ($messageMeta) {
                $messageMeta->lockAction(__('tbe-billing::invoice.locks.user_payment.rejected'), customEmoji: '❌');
            });
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
