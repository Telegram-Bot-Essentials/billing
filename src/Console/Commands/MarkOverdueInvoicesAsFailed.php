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
        $invoices = Invoice::where('status', 'pending')
            ->where('created_at', '<=', now()->subDay())
            ->get();

        foreach ($invoices as $invoice) {
            $invoice->markAsFailed();
        }

        $this->info("Marked {$invoices->count()} overdue invoice(s) as failed.");
    }
}
