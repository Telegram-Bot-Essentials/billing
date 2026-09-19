<?php

declare(strict_types=1);

use Brick\Math\BigDecimal;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use TelegramBotEssentials\Billing\Models\Offer;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Forms\FormState;

const ADMIN_PEER = 700;

beforeEach(function () {
    $this->bot = $this->makeBot();
    $this->makeBotUser($this->bot, ADMIN_PEER, ['power' => Roles::ADMIN->value]);

    // Every sendMessage answers with a fresh message id, like Telegram.
    $counter = 5000;
    $factory = new Factory;
    $factory->fake(function ($request) use (&$counter) {
        if (str_ends_with((string) $request->url(), '/sendMessage')) {
            return Http::response(['ok' => true, 'result' => [
                'message_id' => ++$counter,
                'date' => time(),
                'chat' => ['id' => $request['chat_id'], 'type' => 'private'],
                'text' => $request['text'] ?? '',
            ]]);
        }

        return Http::response(['ok' => true, 'result' => true]);
    });
    Http::swap($factory);
});

/** Recorded Telegram calls of one API method, oldest first. */
function offerTgCalls(string $method): Collection
{
    return Http::recorded(fn ($request) => str_ends_with((string) $request->url(), '/'.$method))
        ->map(fn ($pair) => $pair[0]->data())
        ->values();
}

function offerFormState(): ?FormState
{
    return FormState::fromStateString(test()->bot->botUsers()->where('telegram_user_peer_id', ADMIN_PEER)->sole()->state);
}

function startOfferForm(): void
{
    test()->postWebhookUpdate(test()->bot, test()->makeCallbackQueryUpdate('OFFERS#create?1', peerId: ADMIN_PEER))->assertOk();
}

function tell(string $text): void
{
    test()->postWebhookUpdate(test()->bot, test()->makeMessageUpdate($text, peerId: ADMIN_PEER))->assertOk();
}

function skip(): void
{
    tell(__('tbe::forms.buttons.skip'));
}

function percentage(): string
{
    return __('tbe-billing::offers.wizard.chooseType.percentage');
}

function fixed(): string
{
    return __('tbe-billing::offers.wizard.chooseType.fixed');
}

it('asks the code first and leaves the admin on it when the code is taken', function () {
    Offer::factory()->create(['bot_id' => $this->bot->id, 'code' => 'TAKEN']);
    startOfferForm();

    expect(offerFormState()->step)->toBe('code')
        ->and(offerFormState()->ctx)->toBe(['lastPage' => 1]);

    tell('taken');

    expect(offerFormState()->step)->toBe('code')
        ->and(offerTgCalls('sendMessage')->last()['text'])->toBe(__('tbe-billing::offers.wizard.errors.codeTaken'));
});

it('writes nothing until the summary is confirmed', function () {
    startOfferForm();
    tell('SAVE20');
    tell(percentage());
    tell('20');
    skip();
    skip();
    skip();
    skip();
    skip();
    skip();

    expect(offerFormState()->step)->toBe(FormState::CONFIRM)
        ->and(Offer::count())->toBe(0);
});

it('creates the enabled offer with typed values on Confirm and clears the form', function () {
    startOfferForm();
    tell('save20');
    tell(percentage());
    tell('20');
    tell('50');       // max discount cap
    tell('1000');     // min order price
    tell('90000');    // max order price
    tell('100');      // global limit
    tell('1');        // per-user limit
    tell('30');       // expires in days

    tell(__('tbe::forms.buttons.confirm'));

    $offer = Offer::sole();
    expect($offer->code)->toBe('SAVE20')
        ->and($offer->type)->toBe('percentage')
        ->and($offer->is_enabled)->toBeTrue()
        ->and(BigDecimal::of($offer->amount)->isEqualTo('20'))->toBeTrue()
        ->and(BigDecimal::of($offer->max_discount)->isEqualTo('50'))->toBeTrue()
        ->and(BigDecimal::of($offer->min_price)->isEqualTo('1000'))->toBeTrue()
        ->and(BigDecimal::of($offer->max_price)->isEqualTo('90000'))->toBeTrue()
        ->and($offer->usage_limit)->toBe(100)
        ->and($offer->usage_limit_per_user)->toBe(1)
        ->and($offer->expires_at->isBetween(now()->addDays(29), now()->addDays(31)))->toBeTrue()
        ->and($this->bot->botUsers()->where('telegram_user_peer_id', ADMIN_PEER)->sole()->state)->toBeNull();

    // The confirm prompt became the offer's detail screen, the list was refreshed.
    expect(offerTgCalls('editMessageText')->pluck('text')->join('|'))->toContain('SAVE20');
});

it('leaves optional fields empty when they are skipped', function () {
    startOfferForm();
    tell('FLAT');
    tell(fixed());
    tell('5000');
    foreach (range(1, 5) as $ignored) {
        skip();
    }
    tell(__('tbe::forms.buttons.confirm'));

    $offer = Offer::sole();
    expect($offer->type)->toBe('fixed')
        ->and($offer->max_discount)->toBeNull()
        ->and($offer->min_price)->toBeNull()
        ->and($offer->max_price)->toBeNull()
        ->and($offer->usage_limit)->toBeNull()
        ->and($offer->usage_limit_per_user)->toBeNull()
        ->and($offer->expires_at)->toBeNull();
});

it('only asks for a max discount cap on a percentage offer', function () {
    startOfferForm();
    tell('FLAT');
    tell(fixed());
    tell('5000');

    expect(offerFormState()->step)->toBe('min_price');
});

it('holds the amount to the range of the chosen type', function () {
    startOfferForm();
    tell('CODE');
    tell(percentage());
    tell('150');

    expect(offerFormState()->step)->toBe('amount')
        ->and(offerTgCalls('sendMessage')->last()['text'])->toBe(__('tbe-billing::offers.wizard.errors.percentageOutOfRange'));

    tell('abc');
    expect(offerTgCalls('sendMessage')->last()['text'])->toBe(__('tbe-billing::offers.wizard.errors.mustBeNumeric'));

    tell('25');
    expect(offerFormState()->step)->toBe('max_discount');
});

it('refuses a max order price below the min order price already given', function () {
    startOfferForm();
    tell('CODE');
    tell(fixed());
    tell('100');
    tell('1000');   // min price

    tell('500');    // max price below it

    expect(offerFormState()->step)->toBe('max_price')
        ->and(offerTgCalls('sendMessage')->last()['text'])->toBe(__('tbe-billing::offers.wizard.errors.maxBelowMin'));
});

it('asks for the amount again when the type changes underneath it', function () {
    startOfferForm();
    tell('CODE');
    tell(percentage());
    tell('20');

    tell(__('tbe::forms.buttons.back'));   // amount
    tell(__('tbe::forms.buttons.back'));   // type
    tell(fixed());

    expect(offerFormState()->step)->toBe('amount')
        ->and(offerFormState()->answers)->not->toHaveKey('amount')
        ->and(offerTgCalls('sendMessage')->last()['text'])->toContain(__('tbe-billing::offers.wizard.fields.amount.prompt.fixed'));
});

it('does not create an offer when the code was taken while the form was open', function () {
    startOfferForm();
    tell('RACE');
    tell(fixed());
    tell('100');
    foreach (range(1, 5) as $ignored) {
        skip();
    }

    Offer::factory()->create(['bot_id' => $this->bot->id, 'code' => 'RACE']);
    tell(__('tbe::forms.buttons.confirm'));

    expect(Offer::count())->toBe(1)
        ->and(offerFormState()->step)->toBe('code')
        ->and(offerFormState()->answers)->not->toHaveKey('code');
});

it('creates nothing when the form is cancelled', function () {
    startOfferForm();
    tell('GONE');
    tell(fixed());

    tell(__('tbe::cancel_process.reply_key'));

    expect(Offer::count())->toBe(0)
        ->and($this->bot->botUsers()->where('telegram_user_peer_id', ADMIN_PEER)->sole()->state)->toBeNull();
});

it('does not start for a member', function () {
    $this->makeBotUser($this->bot, 701);

    test()->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate('OFFERS#create?1', peerId: 701))->assertOk();

    expect($this->bot->botUsers()->where('telegram_user_peer_id', 701)->sole()->state)->toBeNull();
});
