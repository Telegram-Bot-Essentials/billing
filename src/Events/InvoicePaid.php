<?php

namespace TelegramBotEssentials\Billing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

class InvoicePaid
{
    use Dispatchable;
    use SerializesModels;

    public Invoice $invoice;
    public ?Bot $bot;
    public ?BotUser $botUser;

    public function __construct(Invoice $invoice, ?Bot $bot = null, ?BotUser $botUser = null)
    {
        $this->invoice = $invoice->withoutRelations();
        $this->bot = $bot ?? $invoice->bot;
        $this->botUser = $botUser ?? $invoice->botUser;
    }
}
