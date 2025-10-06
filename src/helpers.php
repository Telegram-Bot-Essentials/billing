<?php

use TelegramBotEssentials\Billing\Services\Billing;
use TelegramBotEssentials\Billing\Services\Currency;
use TelegramBotEssentials\Billing\Services\Gateways\Gateways;

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