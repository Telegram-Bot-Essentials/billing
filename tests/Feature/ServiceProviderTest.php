<?php

declare(strict_types=1);

use TelegramBotEssentials\Billing\Services\Billing;
use TelegramBotEssentials\Billing\Services\Currency;
use TelegramBotEssentials\Billing\Services\Gateways;
use TelegramBotEssentials\Settings\Services\Settings;

it('registers the billing services on the container', function () {
    expect(app(Billing::class))->toBeInstanceOf(Billing::class)
        ->and(app(Gateways::class))->toBeInstanceOf(Gateways::class);
});

it('registers the billing settings tree', function () {
    $keys = app(Settings::class)->getSettings()->keys();

    expect($keys)->toContain('billing', 'billing.gateways', 'billing.currency');
});

it('defaults the currency setting to USD', function () {
    $bot = $this->makeBot();
    wHook()->setBot($bot);

    expect(app(Currency::class)->getCurrency())->toBe('USD');
});
