<?php

namespace TelegramBotEssentials\Billing\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyFather
{
    private string $currency;

    private string $toCurrency;

    private string $amount;

    public function __construct(string $currency)
    {
        $this->currency = $currency;
    }

    public function amount(string $amount = '1'): self
    {
        $this->amount = $amount;

        return $this;
    }

    public static function from(string $currency): self
    {
        return new self($currency);
    }

    public function to(string $currency)
    {
        $this->toCurrency = $currency;

        return $this->rate();
    }

    public static function USD(): self
    {
        return new self('USD');
    }

    public function toUSD(): string
    {
        $this->toCurrency = 'USD';

        return $this->rate();
    }

    public static function IRR(): self
    {
        return new self('IRR');
    }

    public function toIRR(): string
    {
        $this->toCurrency = 'IRR';

        return $this->rate();
    }

    public static function IRT(): self
    {
        return new self('IRT');
    }

    public function toIRT(): string
    {
        $this->toCurrency = 'IRT';

        return $this->rate();
    }

    public function rate(): string
    {
        if ($this->currency == $this->toCurrency) {
            return $this->amount;
        }
        $key = 'currencyfather-'.$this->currency.'-'.$this->toCurrency.'-'.$this->amount;

        return Cache::remember($key, now()->addHours(6), function () {
            $url = 'https://currency.servicefather.ir/api/currencies/'.$this->toCurrency.'/'.$this->currency.'/'.$this->amount;
            $response = Http::get($url);

            return $response->json()['data']['rate'];
        });
    }
}
