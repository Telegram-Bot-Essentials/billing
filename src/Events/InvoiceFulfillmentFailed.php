<?php

namespace TelegramBotEssentials\Billing\Events;

use TelegramBotEssentials\Billing\Models\Invoice;

/** The invoice is paid, but what it paid for could not be delivered. */
class InvoiceFulfillmentFailed
{
    public function __construct(public readonly Invoice $invoice) {}
}
