<?php

use TelegramBotEssentials\Billing\Services\Billing;
use TelegramBotEssentials\Billing\Services\Currency;
use TelegramBotEssentials\Billing\Services\CurrencyFather;
use TelegramBotEssentials\Billing\Services\Gateways;

if (! function_exists('billing')) {
    function billing(): Billing
    {
        return app(Billing::class);
    }
}

if (! function_exists('currency')) {
    function currency(): Currency
    {
        return app(Currency::class);
    }
}

if (! function_exists('gateways')) {
    function gateways(): Gateways
    {
        return app(Gateways::class);
    }
}

if (! function_exists('priceIn')) {
    function priceIn(string $price): CurrencyFather
    {
        return CurrencyFather::from(settings()->get('billing.currency'))->amount($price);
    }
}
