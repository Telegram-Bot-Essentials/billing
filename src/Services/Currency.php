<?php

namespace TelegramBotEssentials\Billing\Services;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Throwable;

class Currency
{
    private ?string $currency = "USD";
    private string $amount;

    public function __construct()
    {

    }

    function getCurrentCurrencySymbol(): string
    {
        return currency()->getCurrencySymbol($this->currency);
    }

    public function getCurrencySymbol(string $currency): string
    {
        return collect(config('tbe-essence.supported_currencies') ?? [])
            ->where('name', $currency)->first()['symbol'] ?? '?';
    }

    public function amount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function show(): string
    {
        return $this->amount;
    }

    function priceFormat(string $amount, bool $raw = false, ?string $currency = null): string
    {
        $symbol = $this->getCurrencySymbol($currency ?? $this->getCurrency());
        $persianCharacterPattern = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x{FDFC}]/u';
        $separator = preg_match($persianCharacterPattern, $symbol) ? ' ' : '';
        return ($raw ? $amount : $this->currencyFormat($amount, thousandSeparator: ',')) . $separator . $symbol;
    }

    public function getCurrency(): string
    {
        return wHook()->bot()->currency;
    }

    function currencyFormat(string $amount, string $currencyCode = null, $significantDigits = null, $thousandSeparator = null): string
    {
        if ($currencyCode === null) {
            $currencyCode = $this->getCurrency();
        }

        $hasDecimal = is_numeric($amount) && floor($amount) != $amount;

        $decimals = match ($currencyCode) {
            'IRT' => 0,
            'IRR' => 0,
            'USD' => 2,
            default => 2,
        };

        return number_format($amount, $hasDecimal ? ($significantDigits ?? $decimals) : 0, '.', $thousandSeparator);
    }

    public function isCurrencySupported(string $currency): bool
    {
        return in_array($currency, currency()->getSupportedCurrencies());
    }

    public function getSupportedCurrencies(): array
    {
        $currencies = collect(config('tbe-essence.supported_currencies') ?? [])->pluck('name');
        $currencies = $currencies->map(function ($currency) {
            return strtoupper($currency);
        });
        return array_unique(array_merge($currencies->toArray(), ['USD']));
    }

    public function multiply(BigNumber|int|float|string $amount, BigNumber|int|float|string $multiplier): BigDecimal
    {
        $amount = BigDecimal::of($amount);
        $multiplier = BigDecimal::of($multiplier);
        return $amount->multipliedBy($multiplier);
    }
}