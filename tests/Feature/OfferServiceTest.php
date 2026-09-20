<?php

declare(strict_types=1);

use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Billing\Models\Abstract\Order;
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

it('rejects any code when the order type does not allow offers', function () {
    $offer = Offer::factory()->create(['bot_id' => $this->bot->id]);
    $invoice = makeTestInvoice();
    $invoice->setRelation('payable', new class extends Order
    {
        public function getPaidAtAttribute(): ?Carbon
        {
            return null;
        }

        public function getAmountAttribute(): string
        {
            return '0';
        }

        public function getDescriptionAttribute(): string
        {
            return '';
        }

        public function offersAllowed(): bool
        {
            return false;
        }

        public function invoicePaidHook(): void {}

        public function cancelOrderHook(): void {}
    });

    expect(fn () => app(OfferService::class)->redeem($invoice, $offer->code))
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

it('creates an enabled offer from a finished form, leaving skipped fields empty', function () {
    $offer = app(OfferService::class)->createFromAnswers($this->bot->id, [
        'code' => ' save20 ',
        'type' => 'percentage',
        'amount' => '20',
        'max_discount' => '500',
        'min_price' => null,
        'usage_limit' => '10',
        'expires_at' => '7',
    ]);

    expect($offer->exists)->toBeTrue()
        ->and($offer->code)->toBe('SAVE20')
        ->and($offer->is_enabled)->toBeTrue()
        ->and(BigDecimal::of($offer->fresh()->amount)->isEqualTo('20'))->toBeTrue()
        ->and(BigDecimal::of($offer->fresh()->max_discount)->isEqualTo('500'))->toBeTrue()
        ->and($offer->fresh()->min_price)->toBeNull()
        ->and($offer->fresh()->usage_limit)->toBe(10)
        ->and($offer->fresh()->expires_at->isFuture())->toBeTrue();
});

it('parses an optional field to its typed value and rejects a bad one', function () {
    $service = app(OfferService::class);

    expect($service->parseField('min_price', '1500.5'))->toBeInstanceOf(BigDecimal::class)
        ->and($service->parseField('usage_limit', '3'))->toBe(3)
        ->and(fn () => $service->parseField('usage_limit', '0'))->toThrow(ValidationException::class)
        ->and(fn () => $service->parseField('min_price', '-1'))->toThrow(ValidationException::class)
        ->and(fn () => $service->parseField('max_price', '400', '500'))->toThrow(ValidationException::class);

    $service->parseField('max_price', '500', '500');
});

it('holds the amount to the range of its type', function () {
    $service = app(OfferService::class);

    expect($service->parseAmount('100', 'percentage'))->toBeInstanceOf(BigDecimal::class)
        ->and(fn () => $service->parseAmount('100.5', 'percentage'))->toThrow(ValidationException::class)
        ->and(fn () => $service->parseAmount('0', 'fixed'))->toThrow(ValidationException::class)
        ->and(fn () => $service->parseAmount('abc', 'fixed'))->toThrow(ValidationException::class);
});
