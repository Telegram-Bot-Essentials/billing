<?php

namespace TelegramBotEssentials\Billing\Listeners\Invoices;

use Illuminate\Support\Facades\Log;
use TelegramBotEssentials\Billing\Events\InvoiceFailed;
use TelegramBotEssentials\Billing\Jobs\InvoiceFailedHookJob;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Support\WebhookContext;

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

        $invoice->loadMissing(['bot', 'botUser']);

        $bot = $event->context?->resolveBot() ?? $invoice->bot;
        $botUser = $event->context?->resolveBotUser() ?? $invoice->botUser;

        if (!$bot || !$botUser) {
            Log::warning('InvoiceFailed event skipped because bot or bot user could not be resolved.', [
                'invoice_id' => $invoice->getKey(),
            ]);
            return;
        }

        $updatePayload = $event->context?->updatePayload ?? [];
        $context = $event->context ?? new WebhookContext(
            botId: $bot->getKey(),
            botUserId: $botUser->getKey(),
            updatePayload: $updatePayload,
            botToken: $bot->bot_token,
            bot: $bot,
            botUser: $botUser,
        );

        InvoiceFailedHookJob::dispatch($invoice, $bot, $botUser, $updatePayload, $context);
    }
}
