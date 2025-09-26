<?php

namespace TelegramBotEssentials\Billing\Services\Gateways\ZarinPal\Methods;

use TelegramBotEssentials\Billing\Services\Gateways\ZarinPal\ZarinPalMethod;

class PaymentRequest extends ZarinPalMethod
{
    protected string $url = 'https://sandbox.zarinpal.com/pg/v4/payment/request.json';

    public function __construct(int $amount, string $description, string $callback_url)
    {
        Parent::__construct();

        $this->data['amount'] = $amount;
        $this->data['description'] = $description;
        $this->data['callback_url'] = $callback_url;
    }

    public function currency(string $currency): self
    {
        $allowedCurrencies = ['IRR', 'IRT'];

        if (in_array($currency, $allowedCurrencies))
            throw new \ValueError('The value must be one of: ' . implode(', ', $allowedCurrencies));

        $this->data['currency'] = $currency;

        return $this;
    }

    public function wages(array $wage): self
    {
        $requiredKeys = ['iban', 'amount', 'description'];

        $wageKeys = array_keys($wage);

        if (!empty(array_diff($wageKeys, $requiredKeys)) || !empty(array_diff($requiredKeys, $wageKeys)))
            throw new \ValueError('The array must contains only this fields: ' . implode(', ', $requiredKeys));

        $this->data['wages'][] = $wage;

        return $this;
    }

    public function refererId(string $refererId): self
    {
        $this->data['referrer_id'] = $refererId;

        return $this;
    }

    public function metadata(string $card_pan = null,string $mobile = null, string $email = null, string $orderId = null): self
    {
        if ($card_pan) $this->data['metadata']['card_pan'] = $card_pan;
        if ($mobile) $this->data['metadata']['mobile'] = $mobile;
        if ($email) $this->data['metadata']['email'] = $email;
        if ($orderId) $this->data['metadata']['order_id'] = $orderId;

        return $this;
    }
}
