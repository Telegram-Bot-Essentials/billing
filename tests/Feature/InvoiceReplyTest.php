<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Models\TelegramUser;
use TelegramBotEssentials\Essence\Support\WebhookContext;

beforeEach(function () {
    $this->bot = $this->makeBot();
    wHook()->setBot($this->bot);

    TelegramUser::factory()->create(['peer_id' => 222]);
    $this->botUser = BotUser::factory()->create(['bot_id' => $this->bot->id, 'telegram_user_peer_id' => 222]);
    $this->invoice = Invoice::create([
        'bot_id' => $this->bot->id,
        'bot_user_id' => $this->botUser->id,
        'price' => '100000',
        'original_price' => '100000',
        'payable_type' => BotUser::class,
        'payable_id' => 0,
    ]);

    // The status events capture a full WebhookContext, as in OfferServiceTest.
    wHook()->importContext(WebhookContext::fromArray([
        'bot_id' => $this->bot->id,
        'bot_user_id' => $this->botUser->id,
        'update' => new Update([]),
        'bot_token' => $this->bot->bot_token,
        'bot' => $this->bot,
        'bot_user' => $this->botUser,
    ]));

    $this->view = function (int $chatId, int $messageId, string $tag = 'invoice_view') {
        $meta = new MessageMeta;
        $meta->chat_id = $chatId;
        $meta->message_id = $messageId;
        $meta->tag = $tag;
        $meta->action()->associate($this->invoice);
        $meta->save();
    };
});

it('quotes the latest invoice message in the member chat, and sends even if it is gone', function () {
    ($this->view)(222, 10);
    ($this->view)(222, 11);

    expect($this->invoice->replyToView())->toBe([
        'reply_parameters' => ['message_id' => 11, 'allow_sending_without_reply' => true],
    ]);
});

it('quotes nothing when there is no invoice message in the member chat', function () {
    expect($this->invoice->replyToView())->toBe([]);

    ($this->view)(999, 10);
    ($this->view)(222, 12, 'something_else');

    expect($this->invoice->replyToView())->toBe([]);
});

it('replies to the invoice message when telling the member the payment failed', function () {
    Http::fake(['*' => Http::response(['ok' => true, 'result' => ['message_id' => 1, 'chat' => ['id' => 222], 'date' => 1]])]);
    ($this->view)(222, 10);

    $this->invoice->markAsFailed();

    $this->assertTelegramSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
        && $request['text'] === __('tbe-billing::invoice.hooks.status_changed.failed')
        && ($request['reply_parameters']['message_id'] ?? null) === 10
        && ($request['reply_parameters']['allow_sending_without_reply'] ?? null) === true);
});

it('still tells the member when the invoice message is not known', function () {
    Http::fake(['*' => Http::response(['ok' => true, 'result' => ['message_id' => 1, 'chat' => ['id' => 222], 'date' => 1]])]);

    $this->invoice->markAsFailed();

    $this->assertTelegramSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
        && $request['text'] === __('tbe-billing::invoice.hooks.status_changed.failed')
        && ! isset($request['reply_parameters']));
});
