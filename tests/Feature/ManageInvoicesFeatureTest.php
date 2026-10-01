<?php

declare(strict_types=1);

use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Telegram\Features\Admin\ManageInvoicesFeature;
use TelegramBotEssentials\Essence\Models\BotUser;

beforeEach(function () {
    $this->bot = $this->makeBot();
    wHook()->setBot($this->bot);

    $this->alice = $this->makeBotUser($this->bot, 7001);
    $this->bob = $this->makeBotUser($this->bot, 7002);

    foreach ([[$this->alice, 'paid'], [$this->alice, 'pending'], [$this->bob, 'paid']] as [$owner, $status]) {
        $invoice = Invoice::create([
            'bot_id' => $this->bot->id,
            'bot_user_id' => $owner->id,
            'payable_type' => BotUser::class,
            'payable_id' => 0,
            'price' => 5000,
        ]);

        if ($status === 'paid') {
            Invoice::query()->whereKey($invoice->id)->update(['status' => 'paid']);
        }
    }

    $this->callbacks = fn ($response) => collect($response->replyMarkup->toArray()['inline_keyboard'])->flatten(1)->pluck('callback_data');
    // Each invoice row repeats its show button three times; one entry per invoice is what counts.
    $this->shown = fn ($response) => ($this->callbacks)($response)->filter(fn ($data) => str_contains($data, 'MANAGEINVOICES#show'))->unique()->values();
});

it('lists every invoice with the paid figures on top', function () {
    $response = ManageInvoicesFeature::menu();

    expect(($this->shown)($response))->toHaveCount(3)
        ->and($response->text)->toContain(__('tbe-billing::manage_invoices.main.text.list'))
        ->and($response->text)->toContain(class_basename(BotUser::class));
});

it('narrows the list to one member and leaves the shop figures out', function () {
    $response = ManageInvoicesFeature::menu(userId: $this->alice->id);

    $callbacks = ($this->callbacks)($response);

    expect(($this->shown)($response))->toHaveCount(2)
        ->and($callbacks)->toContain(encodeCallback('BOTUSERS', 'show', [$this->alice->id]))
        ->and($response->text)->toBe(__('tbe-billing::manage_invoices.main.text.list'));
});

it('keeps the member filter on every invoice button', function () {
    $shown = ($this->shown)(ManageInvoicesFeature::menu(userId: $this->alice->id));

    expect($shown->every(fn ($data) => str_ends_with($data, '&'.$this->alice->id)))->toBeTrue();
});

it('offers the way back to the profile when a member has no invoices', function () {
    $carol = $this->makeBotUser($this->bot, 7003);

    $response = ManageInvoicesFeature::menu(userId: $carol->id);

    expect(($this->callbacks)($response))->toContain(encodeCallback('BOTUSERS', 'show', [$carol->id]));
});
