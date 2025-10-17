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

class CancelOrderHookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly Invoice $invoice,
        private readonly Bot $bot,
        private readonly BotUser $botUser,
        private readonly array $updatePayload = [],
    ) {
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
            Log::warning('CancelOrderHookJob skipped because dependencies could not be resolved.', [
                'invoice_id' => $this->invoice->getKey(),
                'bot_id' => $this->bot->getKey(),
                'bot_user_id' => $this->botUser->getKey(),
            ]);
            return;
        }

        $api = new Api($bot->bot_token);
        $update = new Update($this->updatePayload);

        wHook()->setApi($api);
        wHook()->setUpdate($update);
        wHook()->setBot($bot);
        wHook()->setUser($botUser);

        try {
            wHook()->api()->sendMessage([
                'chat_id' => $invoice->botUser->telegramUser->peer_id,
                'text' => __('tbe-billing::invoice.hooks.order_reverted'),
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
