<?php

namespace TelegramBotEssentials\Billing\Services\Gateways\ZarinPal\Methods;

use TelegramBotEssentials\Billing\Services\Gateways\ZarinPal\ZarinPalMethod;

class Inquiry extends ZarinPalMethod
{
    protected string $url = 'https://sandbox.zarinpal.com/pg/v4/payment/inquiry.json';

    protected array $data;
    public function __construct(string $authority)
    {
        Parent::__construct();

        $this->data['authority'] = $authority;
    }
}
