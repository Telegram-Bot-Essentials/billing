<?php

namespace TelegramBotEssentials\Billing\Listeners\Invoices;

use Illuminate\Support\Facades\Log;
use TelegramBotEssentials\Billing\Events\InvoicePaid;
use TelegramBotEssentials\Billing\Jobs\InvoicePaidHookJob;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Support\WebhookContext;

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

        $bot = $event->context?->resolveBot() ?? $invoice->bot;
        $botUser = $event->context?->resolveBotUser() ?? $invoice->botUser;

        if (!$bot || !$botUser) {
            Log::warning('InvoicePaid event skipped because bot or bot user could not be resolved.', [
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

        InvoicePaidHookJob::dispatch($invoice, $bot, $botUser, $updatePayload, $context);
    }
}
