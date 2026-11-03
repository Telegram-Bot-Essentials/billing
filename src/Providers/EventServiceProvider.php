<?php

namespace TelegramBotEssentials\Billing\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use TelegramBotEssentials\Billing\Events\InvoiceFailed;
use TelegramBotEssentials\Billing\Events\InvoiceFulfillmentFailed;
use TelegramBotEssentials\Billing\Events\InvoicePaid;
use TelegramBotEssentials\Billing\Events\InvoicePending;
use TelegramBotEssentials\Billing\Events\InvoiceRevoked;
use TelegramBotEssentials\Billing\Listeners\Invoices\AlertStaffOfPurchase;
use TelegramBotEssentials\Billing\Listeners\Invoices\ConfirmOfferRedemptionOnInvoicePaid;
use TelegramBotEssentials\Billing\Listeners\Invoices\DispatchInvoiceFailedHooks;
use TelegramBotEssentials\Billing\Listeners\Invoices\DispatchInvoicePaidHooks;
use TelegramBotEssentials\Billing\Listeners\Invoices\DispatchInvoicePendingHooks;
use TelegramBotEssentials\Billing\Listeners\Invoices\DispatchInvoiceRevokedHooks;
use TelegramBotEssentials\Billing\Listeners\Invoices\InvokeInvoiceHooks;
use TelegramBotEssentials\Billing\Listeners\Invoices\ReleaseOfferRedemptionOnInvoiceRevoked;
use TelegramBotEssentials\Billing\Listeners\Invoices\TellAboutFailedFulfillment;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InvoiceFulfillmentFailed::class => [
            TellAboutFailedFulfillment::class,
        ],
        InvoicePaid::class => [
            ConfirmOfferRedemptionOnInvoicePaid::class,
            DispatchInvoicePaidHooks::class,
            AlertStaffOfPurchase::class,
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
            ReleaseOfferRedemptionOnInvoiceRevoked::class,
            DispatchInvoiceRevokedHooks::class,
            InvokeInvoiceHooks::class,
        ],
    ];
}
