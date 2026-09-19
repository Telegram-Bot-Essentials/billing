<?php

declare(strict_types=1);

use TelegramBotEssentials\Billing\Models\Offer;
use TelegramBotEssentials\Billing\Telegram\Features\Admin\OffersFeature;

beforeEach(function () {
    $this->bot = $this->makeBot();
    wHook()->setBot($this->bot);
});

/** An offer whose amount reads back the way the 30-digit decimal column returns it. */
function offerWithAmount(string $type, string $amount): Offer
{
    $offer = Offer::factory()->create(['bot_id' => test()->bot->id, 'type' => $type]);
    $offer->amount = $amount;

    return $offer;
}

it('shows a percentage without the decimal column\'s trailing zeros', function (string $stored, string $shown) {
    $offer = offerWithAmount('percentage', $stored);

    expect(OffersFeature::show($offer)->text)->toContain($shown.'%')->not->toContain('000');
})->with([
    'a whole percentage' => ['100.000000000000000000000000000000', '100'],
    'a fractional percentage' => ['12.500000000000000000000000000000', '12.5'],
    'a small percentage' => ['0.500000000000000000000000000000', '0.5'],
    'already trimmed' => ['20', '20'],
]);

it('shows the same trimmed percentage on the list button', function () {
    $offer = Offer::factory()->create(['bot_id' => $this->bot->id, 'type' => 'percentage', 'amount' => '100']);
    Offer::query()->whereKey($offer->id)->update(['amount' => '100.000000000000000000000000000000']);

    $labels = collect(OffersFeature::menu()->replyMarkup->toArray()['inline_keyboard'])->flatten(1)->pluck('text');

    expect($labels->join('|'))->toContain('100%')->not->toContain('000');
});

it('formats a fixed discount as money, without empty decimals', function () {
    $offer = offerWithAmount('fixed', '5000.000000000000000000000000000000');

    expect(OffersFeature::show($offer)->text)->toContain('5,000')->not->toContain('000000');
});
