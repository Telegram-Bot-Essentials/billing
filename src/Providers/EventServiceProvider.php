<?php

namespace TelegramBotEssentials\Billing\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use TelegramBotEssentials\Billing\Events\InvoiceFailed;
use TelegramBotEssentials\Billing\Events\InvoicePaid;
use TelegramBotEssentials\Billing\Events\InvoicePending;
use TelegramBotEssentials\Billing\Events\InvoiceRevoked;
use TelegramBotEssentials\Billing\Listeners\Invoices\DispatchInvoiceFailedHooks;
use TelegramBotEssentials\Billing\Listeners\Invoices\DispatchInvoicePaidHooks;
use TelegramBotEssentials\Billing\Listeners\Invoices\DispatchInvoicePendingHooks;
use TelegramBotEssentials\Billing\Listeners\Invoices\DispatchInvoiceRevokedHooks;
use TelegramBotEssentials\Billing\Listeners\Invoices\InvokeInvoiceHooks;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InvoicePaid::class => [
            DispatchInvoicePaidHooks::class,
            InvokeInvoiceHooks::class,
        ],
        InvoiceFailed::class => [
            DispatchInvoiceFailedHooks::class,
            InvokeInvoiceHooks::class,
        ],
        InvoicePending::class => [
            DispatchInvoicePendingHooks::class,
            InvokeInvoiceHooks::class,
        ],
        InvoiceRevoked::class => [
            DispatchInvoiceRevokedHooks::class,
            InvokeInvoiceHooks::class,
        ],
    ];
}
