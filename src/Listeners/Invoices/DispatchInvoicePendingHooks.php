<?php

namespace TelegramBotEssentials\Billing\Listeners\Invoices;

use Illuminate\Support\Facades\Log;
use TelegramBotEssentials\Billing\Events\InvoicePending;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Telegram\Features\Member\InvoiceFeature;

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

        try {
            wHook()->api()->sendMessage([
                'chat_id' => $invoice->botUser->telegramUser->peer_id,
                'text' => __('tbe-billing::invoice.hooks.status_changed.pending'),
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);

            $telegramResponse = InvoiceFeature::invoice($this->invoice);
            $invoice->messageMeta()->where('tag', 'invoice_view')->get()->each(function ($messageMeta) use ($telegramResponse) {
                $messageMeta->updateAndContinueAction($telegramResponse);
            });
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
