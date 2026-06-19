<?php

namespace TelegramBotEssentials\Billing\Console\Commands;

use Illuminate\Console\Command;
use TelegramBotEssentials\Billing\Models\Invoice;

class MarkOverdueInvoicesAsFailed extends Command
{
    protected $signature = 'billing:mark-overdue-invoices-failed';

    protected $description = 'Mark pending invoices as failed if they have been unpaid for more than 1 day';

    public function handle(): void
    {
        $count = 0;

        Invoice::where('status', 'pending')
            ->where('created_at', '<=', now()->subDay())
            ->chunk(100, function ($invoices) use (&$count) {
                foreach ($invoices as $invoice) {
                    $invoice->markAsFailed();
                    $count++;
                }
            });

        $this->info("Marked {$count} overdue invoice(s) as failed.");
    }
}
