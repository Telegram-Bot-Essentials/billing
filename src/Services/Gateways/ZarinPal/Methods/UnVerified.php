<?php

namespace TelegramBotEssentials\Billing\Services\Gateways\ZarinPal\Methods;

use TelegramBotEssentials\Billing\Services\Gateways\ZarinPal\ZarinPalMethod;

class UnVerified extends ZarinPalMethod
{
    protected string $url = 'https://sandbox.zarinpal.com/pg/v4/payment/unVerified.json';

    protected array $data;
}
