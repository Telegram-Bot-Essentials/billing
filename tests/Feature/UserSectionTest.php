<?php

declare(strict_types=1);

use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Models\BotUser;

it('adds an invoices button to a member\'s profile', function () {
    $bot = $this->makeBot();
    wHook()->setBot($bot);
    $member = $this->makeBotUser($bot, 7001);
    Invoice::create([
        'bot_id' => $bot->id,
        'bot_user_id' => $member->id,
        'payable_type' => BotUser::class,
        'payable_id' => 0,
        'price' => 5000,
    ]);

    $section = userManagementSections()->getSectionsFor($member)->firstWhere('key', 'invoices');

    expect($section)->not->toBeNull()
        ->and($section->labelFor($member))->toContain('1')
        ->and($section->targetFor($member))->toBe(encodeCallback('MANAGEINVOICES', 'user', [$member->id]));
});

it('sorts members by what they have paid', function () {
    $bot = $this->makeBot();
    wHook()->setBot($bot);
    $small = $this->makeBotUser($bot, 7101);
    $big = $this->makeBotUser($bot, 7102);
    $none = $this->makeBotUser($bot, 7103);

    foreach ([[$small, 1000, 'paid'], [$big, 9000, 'paid'], [$big, 5000, 'pending'], [$none, 7000, 'failed']] as [$owner, $price, $status]) {
        $invoice = Invoice::create([
            'bot_id' => $bot->id,
            'bot_user_id' => $owner->id,
            'payable_type' => BotUser::class,
            'payable_id' => 0,
            'price' => $price,
        ]);
        Invoice::query()->whereKey($invoice->id)->update(['status' => $status]);
    }

    $sorts = botUserSorts();
    $ordered = fn (string $direction) => $sorts->apply('total_paid', BotUser::query(), $direction)->pluck('id')->all();

    expect($ordered('desc'))->toBe([$big->id, $small->id, $none->id])
        ->and($ordered('asc'))->toBe([$none->id, $small->id, $big->id])
        ->and($sorts->getSort('total_paid')->displayValue($big))->toContain('9,000')->not->toContain('14,000');
});
