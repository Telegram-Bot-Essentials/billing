<?php

use TelegramBotEssentials\Billing\Services\Billing;

if (!function_exists('billing')) {
    function billing(): Billing
    {
        return app(Billing::class);
    }
}