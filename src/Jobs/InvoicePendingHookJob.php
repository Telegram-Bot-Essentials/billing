<?php

namespace TelegramBotEssentials\Billing\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Telegram\Features\Member\InvoiceFeature;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Support\Webhook;
use TelegramBotEssentials\Essence\Support\WebhookContext;

class InvoicePendingHookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private WebhookContext $context;

    public function __construct(
        private readonly Invoice $invoice,
    ) {
        $this->context = WebhookContext::capture();
        $this->queue = 'billing';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->context->apply();

        try {
            wHook()->api()->sendMessage([
                'chat_id' => $this->invoice->botUser->telegramUser->peer_id,
                'text' => __('tbe-billing::invoice.hooks.status_changed.pending'),
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);

            $telegramResponse = InvoiceFeature::invoice($this->invoice);
            $this->invoice->messageMeta()->where('tag', 'invoice_view')->get()->each(function ($messageMeta) use ($telegramResponse) {
                $messageMeta->updateAndContinueAction($telegramResponse);
            });
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

    }
}
