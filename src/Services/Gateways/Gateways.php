<?php

namespace TelegramBotEssentials\Billing\Services\Gateways;

use TelegramBotEssentials\Billing\Services\Gateways\ZarinPal\ZarinPal;
use TelegramBotEssentials\GatewayZibal\Services\Zibal\Zibal;

class Gateways
{
    public function zibal(): Zibal
    {
        return app(Zibal::class);
    }

    public function zarinpal(): Zarinpal
    {
        return app(Zarinpal::class);
    }

    public function wallet(): Wallet
    {
        return app(Wallet::class);
    }
}