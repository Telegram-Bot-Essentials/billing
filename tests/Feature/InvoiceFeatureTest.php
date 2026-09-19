<?php

declare(strict_types=1);

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Billing\DTOs\Gateway;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Models\Offer;
use TelegramBotEssentials\Billing\Telegram\Features\Member\InvoiceFeature;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;

beforeEach(function () {
    $this->bot = $this->makeBot();
    wHook()->setBot($this->bot);

    TelegramUser::factory()->create(['peer_id' => 222]);
    $this->botUser = BotUser::factory()->create(['bot_id' => $this->bot->id, 'telegram_user_peer_id' => 222]);
});

function summaryInvoice(string $price, string $originalPrice, ?Offer $offer = null): Invoice
{
    return Invoice::create([
        'bot_id' => test()->bot->id,
        'bot_user_id' => test()->botUser->id,
        'price' => $price,
        'original_price' => $originalPrice,
        'offer_id' => $offer?->id,
        'payable_type' => BotUser::class,
        'payable_id' => 0,
    ]);
}

it('states the amount due when no offer is applied', function () {
    $invoice = summaryInvoice('100000', '100000');

    expect(InvoiceFeature::offerSummary($invoice))->toBeNull()
        ->and(InvoiceFeature::paymentSummary($invoice))->toContain('100,000')->not->toContain('SAVE20');
});

it('states the amount due, the offer code and the price it replaced when an offer is applied', function () {
    $offer = Offer::factory()->create(['bot_id' => $this->bot->id, 'code' => 'SAVE20']);
    $invoice = summaryInvoice('80000', '100000', $offer);

    $summary = InvoiceFeature::paymentSummary($invoice);

    expect($summary)->toContain('80,000')
        ->and($summary)->toContain('SAVE20')
        ->and($summary)->toContain('<s>100,000')
        ->and(InvoiceFeature::offerSummary($invoice))->toContain('SAVE20');
});

it('keeps showing the offer on the invoice page', function () {
    gateways()->addGateway(new Gateway('stub', 'Stub', fn () => Keyboard::inlineButton(['text' => 'Pay', 'callback_data' => 'X#pay'])));
    $offer = Offer::factory()->create(['bot_id' => $this->bot->id, 'code' => 'SAVE20']);
    $invoice = summaryInvoice('80000', '100000', $offer);

    expect(InvoiceFeature::invoice($invoice)->text)->toContain('SAVE20')->toContain('<s>100,000');
});
