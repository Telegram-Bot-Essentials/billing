<?php

declare(strict_types=1);

use TelegramBotEssentials\Billing\Services\Currency;
use TelegramBotEssentials\Settings\Services\Settings;

// Currency reads `billing.currency` in its constructor via settings(),
// which resolves the current bot from the webhook context - stand one up.
beforeEach(function () {
    wHook()->setBot($this->makeBot());
});

it('resolves the currency symbol from the package config', function () {
    $currency = app(Currency::class);

    expect($currency->getCurrencySymbol('USD'))->toBe('$')
        ->and($currency->getCurrencySymbol('IRT'))->toBe('تومان')
        ->and($currency->getCurrencySymbol('NOPE'))->toBe('?');
});

it('groups thousands and keeps decimals only when the amount has them', function () {
    $currency = app(Currency::class);

    expect($currency->currencyFormat('1234567', 'USD', thousandSeparator: ','))->toBe('1,234,567')
        ->and($currency->currencyFormat('1234.5', 'USD', thousandSeparator: ','))->toBe('1,234.50');
});

it('appends the selected currency symbol to a formatted price', function () {
    app(Settings::class)->set('billing.currency', 'IRT');
    app()->forgetInstance(Currency::class);

    expect(app(Currency::class)->priceFormat('50000'))->toBe('50,000 تومان');
});
