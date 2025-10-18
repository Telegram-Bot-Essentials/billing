<?php

namespace TelegramBotEssentials\Billing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Support\Webhook;
use TelegramBotEssentials\Essence\Support\WebhookContext;

class InvoiceFailedHookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private WebhookContext $context;

    public function __construct(
        private readonly Invoice $invoice,
        private readonly Bot $bot,
        private readonly BotUser $botUser,
        private readonly array|Update $updatePayload = [],
    ) {
        $this->context = WebhookContext::capture();
        $this->queue = 'billing';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invoice = $this->invoice->fresh();
        $bot = $this->bot->fresh();
        $botUser = $this->botUser->fresh();

        if (!$invoice || !$bot || !$botUser) {
            Log::warning('InvoiceFailedHookJob skipped because dependencies could not be resolved.', [
                'invoice_id' => $this->invoice->getKey(),
                'bot_id' => $this->bot->getKey(),
                'bot_user_id' => $this->botUser->getKey(),
            ]);
            return;
        }

        $contextApplied = $this->context?->apply() ?? false;

        if (!$contextApplied) {
            Webhook::clear();
            Webhook::setApi(new Api($bot->bot_token));
            $update = $this->updatePayload instanceof Update
                ? $this->updatePayload
                : new Update($this->updatePayload);
            Webhook::setUpdate($update);
            Webhook::setBot($bot);
            Webhook::setUser($botUser);
        }

        try {
            wHook()->api()->sendMessage([
                'chat_id' => $invoice->botUser->telegramUser->peer_id,
                'text' => __('tbe-billing::invoice.hooks.status_changed.failed'),
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);

            $invoice->messageMeta()->where('tag', 'invoice_view')->get()->each(function ($messageMeta) {
                $messageMeta->lockAction(__('tbe-gateway-card::invoice.to_card.lock-keys.user-payment_rejected'), customEmoji: '❌');
            });
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

    }
}
