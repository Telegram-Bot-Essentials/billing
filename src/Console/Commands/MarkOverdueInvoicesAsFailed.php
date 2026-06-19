<?php

namespace TelegramBotEssentials\Billing\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laravel\Telescope\Telescope;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Support\WebhookContext;

class MarkOverdueInvoicesAsFailed extends Command
{
    protected $signature = 'tbe:billing:mark-overdue-invoices-failed';

    protected $description = 'Mark pending invoices as failed if they have been unpaid for more than 1 day';

    public function handle(): void
    {
        $count = 0;

        Invoice::where('status', 'pending')
            ->where('created_at', '<=', now()->subDay())
            ->chunkById(100, function ($invoices) use (&$count) {
                foreach ($invoices as $invoice) {
                    $botUser = $invoice->botUser;
                    $bot = $botUser->bot;

                    wHook()->importContext(WebhookContext::fromArray([
                        "bot_id" => $bot->id,
                        "bot_user_id" => $botUser->id,
                        "update" => new Update([]),
                        "bot_token" => $bot->bot_token,
                        "bot" => $bot,
                        "bot_user" => $botUser,
                    ]));

                    $invoice->markAsFailed();

                    wHook()->clear();
                    $count++;
                }
            });

        $this->info("Marked {$count} overdue invoice(s) as failed.");
    }
}
