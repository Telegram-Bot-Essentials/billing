<?php

namespace TelegramBotEssentials\Billing\Console\Commands;

use Illuminate\Console\Command;
use TelegramBotEssentials\Billing\Models\Offer;

class PruneAbandonedOfferDrafts extends Command
{
    protected $signature = 'tbe:billing:prune-abandoned-offer-drafts';

    protected $description = 'Delete offer-creation wizards left mid-flow (never reached the required fields) for over an hour';

    public function handle(): void
    {
        $count = Offer::whereNull('type')
            ->where('created_at', '<=', now()->subHour())
            ->delete();

        if ($count > 0) {
            tbeLog('billing')->info('Pruned abandoned offer drafts', ['count' => $count]);
        }

        $this->info("Pruned {$count} abandoned offer draft(s).");
    }
}
