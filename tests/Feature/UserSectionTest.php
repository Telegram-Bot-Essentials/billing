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
