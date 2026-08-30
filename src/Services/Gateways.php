<?php

namespace TelegramBotEssentials\Billing\Services;

use Illuminate\Support\Collection;
use TelegramBotEssentials\Billing\DTOs\Gateway;

class Gateways
{
    private Collection $gateways;

    public function __construct()
    {
        $this->gateways = collect();
    }

    public function addGateway(Gateway $gateway): void
    {
        $this->gateways->put($gateway->key, $gateway);
    }

    public function getGateways(): Collection
    {
        return $this->gateways;
    }

    public function getGateway(string $key): Gateway
    {
        return $this->gateways->get($key);
    }
}
