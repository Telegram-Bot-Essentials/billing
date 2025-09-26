<?php

namespace TelegramBotEssentials\Billing\Services\Gateways\ZarinPal;

use Illuminate\Support\Facades\Http;

abstract class ZarinPalMethod
{
    protected string $url;
    protected array $data;

    public function __construct()
    {
        $this->data['referrer_id'] = '06657gr';
    }

    public function execute()
    {
        $this->data['merchant_id'] = wHook()->bot()->settings->zarinpal_merchant_id;
        $result = HTTP::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($this->url, $this->data);
        return $result->json();
    }
}
