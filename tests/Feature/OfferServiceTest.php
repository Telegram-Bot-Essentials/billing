<?php

declare(strict_types=1);

use Brick\Math\BigDecimal;
use Illuminate\Validation\ValidationException;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Models\Offer;
use TelegramBotEssentials\Billing\Models\OfferRedemption;
use TelegramBotEssentials\Billing\Services\OfferService;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use TelegramBotEssentials\Essence\Support\WebhookContext;

beforeEach(function () {
    $this->bot = $this->makeBot();
    wHook()->setBot($this->bot);

    TelegramUser::factory()->create(['peer_id' => 111]);
    $this->botUser = BotUser::factory()->create([
        'bot_id' => $this->bot->id,
        'telegram_user_peer_id' => 111,
    ]);
});

function makeTestInvoice(int $price = 100000, ?BotUser $botUser = null): Invoice
{
    return Invoice::create([
        'bot_id' => test()->bot->id,
        'bot_user_id' => ($botUser ?? test()->botUser)->id,
        'price' => (string) $price,
        'original_price' => (string) $price,
        // No real polymorphic order to attach in these service-level tests -
        // the `payable` columns are NOT NULL (every real invoice has one via
        // Order::invoice()->create()). Point at a real, harmless class with
        // a bogus id rather than a fake string: MorphTo::createModelByType()
        // throws a hard Error (uncaught by ExceptionHandler) for a class
        // that doesn't exist at all, but resolves quietly to null for a
        // real class with no matching row.
        'payable_type' => BotUser::class,
        'payable_id' => 0,
    ]);
}

it('rejects an unknown code', function () {
    $invoice = makeTestInvoice();

    expect(fn () => app(OfferService::class)->redeem($invoice, 'NOPE'))
        ->toThrow(ValidationException::class);
});

it('rejects a disabled code', function () {
    $offer = Offer::factory()->disabled()->create(['bot_id' => $this->bot->id]);
    $invoice = makeTestInvoice();

    expect(fn () => app(OfferService::class)->redeem($invoice, $offer->code))
        ->toThrow(ValidationException::class);
});

it('rejects an expired code', function () {
    $offer = Offer::factory()->expired()->create(['bot_id' => $this->bot->id]);
    $invoice = makeTestInvoice();

    expect(fn () => app(OfferService::class)->redeem($invoice, $offer->code))
        ->toThrow(ValidationException::class);
});

it('rejects an order below the minimum price', function () {
    $offer = Offer::factory()->create(['bot_id' => $this->bot->id, 'min_price' => '50000']);
    $invoice = makeTestInvoice(10000);

    expect(fn () => app(OfferService::class)->redeem($invoice, $offer->code))
        ->toThrow(ValidationException::class);
});

it('rejects an order above the maximum price', function () {
    $offer = Offer::factory()->create(['bot_id' => $this->bot->id, 'max_price' => '50000']);
    $invoice = makeTestInvoice(100000);

    expect(fn () => app(OfferService::class)->redeem($invoice, $offer->code))
        ->toThrow(ValidationException::class);
});

it('rejects once the global usage limit is reached', function () {
    $offer = Offer::factory()->create(['bot_id' => $this->bot->id, 'usage_limit' => 1]);
    OfferRedemption::factory()->create(['offer_id' => $offer->id, 'bot_user_id' => $this->botUser->id, 'invoice_id' => makeTestInvoice()->id]);

    $invoice = makeTestInvoice();

    expect(fn () => app(OfferService::class)->redeem($invoice, $offer->code))
        ->toThrow(ValidationException::class);
});

it('rejects once the per-user usage limit is reached, but still lets other users redeem', function () {
    $offer = Offer::factory()->create(['bot_id' => $this->bot->id, 'usage_limit_per_user' => 1]);
    OfferRedemption::factory()->create(['offer_id' => $offer->id, 'bot_user_id' => $this->botUser->id, 'invoice_id' => makeTestInvoice()->id]);

    $invoice = makeTestInvoice();
    expect(fn () => app(OfferService::class)->redeem($invoice, $offer->code))->toThrow(ValidationException::class);

    TelegramUser::factory()->create(['peer_id' => 222]);
    $otherUser = BotUser::factory()->create(['bot_id' => $this->bot->id, 'telegram_user_peer_id' => 222]);
    $otherInvoice = makeTestInvoice(100000, $otherUser);

    app(OfferService::class)->redeem($otherInvoice, $offer->code);
    expect($otherInvoice->offer_id)->toBe($offer->id);
});

it('applies a percentage discount and caps it at max_discount', function () {
    $offer = Offer::factory()->create([
        'bot_id' => $this->bot->id,
        'type' => 'percentage',
        'amount' => '50',
        'max_discount' => '20000',
    ]);
    $invoice = makeTestInvoice(100000);

    app(OfferService::class)->redeem($invoice, $offer->code);

    expect($invoice->offer_id)->toBe($offer->id)
        ->and(BigDecimal::of($invoice->price)->isEqualTo('80000'))->toBeTrue();
});

it('applies a fixed discount clamped to the order price', function () {
    $offer = Offer::factory()->fixed('999999')->create(['bot_id' => $this->bot->id]);
    $invoice = makeTestInvoice(100000);

    app(OfferService::class)->redeem($invoice, $offer->code);

    expect(BigDecimal::of($invoice->price)->isEqualTo('0'))->toBeTrue();
});

it('matches codes case-insensitively', function () {
    $offer = Offer::factory()->create(['bot_id' => $this->bot->id, 'code' => 'SAVE20']);
    $invoice = makeTestInvoice();

    app(OfferService::class)->redeem($invoice, 'save20');

    expect($invoice->offer_id)->toBe($offer->id);
});

it('restores the original price when a code is removed', function () {
    $offer = Offer::factory()->fixed('10000')->create(['bot_id' => $this->bot->id]);
    $invoice = makeTestInvoice(100000);

    app(OfferService::class)->redeem($invoice, $offer->code);
    app(OfferService::class)->remove($invoice);

    expect($invoice->offer_id)->toBeNull()
        ->and((string) $invoice->price)->toBe($invoice->original_price);
});

it('only confirms a redemption once payment succeeds, freeing it back up if reverted', function () {
    $offer = Offer::factory()->fixed('10000')->create(['bot_id' => $this->bot->id]);
    $invoice = makeTestInvoice(100000);
    app(OfferService::class)->redeem($invoice, $offer->code);

    expect(OfferRedemption::query()->where('invoice_id', $invoice->id)->exists())->toBeFalse();

    // markAsPaid()/markAsFailed() fire events whose constructor captures a
    // full WebhookContext - setBot() alone (as in the rest of this file)
    // isn't enough, so import one the same way the scheduled
    // MarkOverdueInvoicesAsFailed command does for out-of-request calls.
    wHook()->importContext(WebhookContext::fromArray([
        'bot_id' => $this->bot->id,
        'bot_user_id' => $this->botUser->id,
        'update' => new Update([]),
        'bot_token' => $this->bot->bot_token,
        'bot' => $this->bot,
        'bot_user' => $this->botUser,
    ]));

    $invoice->markAsPaid();
    expect(OfferRedemption::query()->where('invoice_id', $invoice->id)->exists())->toBeTrue();

    $invoice->markAsFailed();
    expect(OfferRedemption::query()->where('invoice_id', $invoice->id)->exists())->toBeFalse();
});
