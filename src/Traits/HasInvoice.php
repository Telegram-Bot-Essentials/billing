<?php

namespace TelegramBotEssentials\Billing\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use TelegramBotEssentials\Billing\Models\Invoice;

trait HasInvoice
{
    public function invoice(): MorphOne
    {
        return $this->morphOne(Invoice::class, 'payable')->latest();
    }

    abstract public function invoicePaidHook(): void;

    abstract public function cancelOrderHook(): void;

    public function invoicePendingHook(): void
    {
        // Optional hooks
    }

    public function invoiceFailedHook(): void
    {
        // Optional hooks
    }
}
