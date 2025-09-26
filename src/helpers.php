<?php

use TelegramBotEssentials\Billing\Services\Billing;
use TelegramBotEssentials\Billing\Services\Currency;

if (!function_exists('billing')) {
    function billing(): Billing
    {
        return app(Billing::class);
    }
}

if (!function_exists('currency')){
    function currency(): Currency
    {
        return app(Currency::class);
    }
}

if (!function_exists('gateways')) {
    function gateways(): Gateways
    {
        return app(Gateways::class);
    }
}