<?php

namespace TelegramBotEssentials\Billing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

class InvoicePaidHookJob implements ShouldQueue
{
    use Queueable;

    private Invoice $invoice;
    private Api $api;
    private Update $update;
    private Bot $bot;
    private BotUser $botUser;

    /**
     * Create a new job instance.
     */
    public function __construct(Api $api, Update $update, Bot $bot, BotUser $botUser, Invoice $invoice)
    {
        $this->queue = 'billing';

        $this->api = $api;
        $this->update = $update;
        $this->bot = $bot;
        $this->botUser = $botUser;
        $this->invoice = $invoice;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        wHook()->setApi($this->api);
        wHook()->setUpdate($this->update);
        wHook()->setBot($this->bot);
        wHook()->setUser($this->botUser);

        try {
            wHook()->api()->sendMessage([
                'chat_id' => $this->invoice->botUser->telegramUser->peer_id,
                'text' => "Your invoice status changed to paid", // TODO: Localize this message
                'reply_markup' => wHook()->user()->getKeyboard(),
            ]);

            $this->invoice->messageMeta()->where('tag', 'invoice_view')->get()->each(function ($messageMeta) {
                $messageMeta->lockAction(__('tbe-billing::invoice.to_card.lock-keys.user-payment_accepted'), customEmoji: "✅");
            });
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        $payable = $this->invoice->payable ?? null;
        if ($payable && method_exists($payable, 'invoicePaidHook')) {
            $payable->invoicePaidHook();
        }
    }
}
