<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Access\Models\Role;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Services\PurchaseAlerts;
use TelegramBotEssentials\Essence\Models\BotUser;

const BUYER_PEER = 7401;
const STAFF_PEER = 7402;
const OTHER_STAFF_PEER = 7403;

beforeEach(function () {
    config(['queue.default' => 'sync']);

    $this->bot = $this->makeBot();
    $this->buyer = $this->makeBotUser($this->bot, BUYER_PEER);

    tenancy()->initialize($this->bot);
    wHook()->setBot($this->bot);
    wHook()->setUser($this->buyer);
    wHook()->setApi(telegramApi((string) $this->bot->bot_token));
    wHook()->setUpdate(new Update(['update_id' => 1]));

    $this->paidInvoice = function (string $price = '5000'): Invoice {
        $invoice = Invoice::create([
            'bot_id' => $this->bot->id,
            'bot_user_id' => $this->buyer->id,
            'payable_type' => BotUser::class,
            'payable_id' => 0,
            'price' => $price,
        ]);
        $invoice->markAsPaid();

        return $invoice;
    };

    $this->alertsTo = fn (int $peerId) => Http::recorded(fn ($request) => str_ends_with((string) $request->url(), '/sendMessage')
        && (int) $request['chat_id'] === $peerId
        && str_contains((string) $request['text'], 'New purchase'))->count();

    $this->roleWith = function (int ...$peers): Role {
        $role = Role::create(['bot_id' => $this->bot->id, 'name' => 'Sales', 'abilities' => []]);

        foreach ($peers as $peer) {
            $role->users()->attach($this->makeBotUser($this->bot, $peer)->id);
        }

        return $role;
    };
});

it('stays quiet until the owner turns it on', function () {
    ($this->paidInvoice)();

    expect(($this->alertsTo)((int) $this->bot->bot_owner_peer_id))->toBe(0);
});

it('goes to the owner by default', function () {
    settings()->set('billing.purchase_alerts.status', true);

    ($this->paidInvoice)();

    expect(($this->alertsTo)((int) $this->bot->bot_owner_peer_id))->toBe(1);
});

it('goes to everyone holding the chosen role, and not the owner', function () {
    $role = ($this->roleWith)(STAFF_PEER, OTHER_STAFF_PEER);
    settings()->set('billing.purchase_alerts.status', true);
    settings()->set('billing.purchase_alerts.target', (string) $role->id);

    ($this->paidInvoice)();

    expect(($this->alertsTo)(STAFF_PEER))->toBe(1)
        ->and(($this->alertsTo)(OTHER_STAFF_PEER))->toBe(1)
        ->and(($this->alertsTo)((int) $this->bot->bot_owner_peer_id))->toBe(0);
});

it('falls back to the owner when nobody holds the role', function () {
    $role = ($this->roleWith)();
    settings()->set('billing.purchase_alerts.status', true);
    settings()->set('billing.purchase_alerts.target', (string) $role->id);

    ($this->paidInvoice)();

    expect(($this->alertsTo)((int) $this->bot->bot_owner_peer_id))->toBe(1);
});

it('falls back to the owner when the role was deleted', function () {
    $role = ($this->roleWith)(STAFF_PEER);
    settings()->set('billing.purchase_alerts.status', true);
    settings()->set('billing.purchase_alerts.target', (string) $role->id);
    $role->delete();

    ($this->paidInvoice)();

    expect(($this->alertsTo)((int) $this->bot->bot_owner_peer_id))->toBe(1);
});

it('reaches the other staff when one has blocked the bot', function () {
    $role = ($this->roleWith)(STAFF_PEER, OTHER_STAFF_PEER);
    settings()->set('billing.purchase_alerts.status', true);
    settings()->set('billing.purchase_alerts.target', (string) $role->id);

    $factory = new Factory;
    $factory->fake(fn ($request) => (int) ($request['chat_id'] ?? 0) === STAFF_PEER
        ? Http::response(['ok' => false, 'error_code' => 403, 'description' => 'Forbidden: bot was blocked by the user'], 403)
        : Http::response(['ok' => true, 'result' => ['message_id' => 1, 'date' => 1, 'chat' => ['id' => 1, 'type' => 'private']]]));
    Http::swap($factory);

    ($this->paidInvoice)();

    expect(($this->alertsTo)(OTHER_STAFF_PEER))->toBe(1);
});

it('leaves out free orders and prizes', function () {
    settings()->set('billing.purchase_alerts.status', true);

    ($this->paidInvoice)('0');

    expect(($this->alertsTo)((int) $this->bot->bot_owner_peer_id))->toBe(0);
});

it('alerts nobody when the payment rolls back', function () {
    settings()->set('billing.purchase_alerts.status', true);

    try {
        DB::transaction(function () {
            ($this->paidInvoice)();
            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
    }

    expect(($this->alertsTo)((int) $this->bot->bot_owner_peer_id))->toBe(0);
});

it('offers the owner and every role as targets', function () {
    $role = ($this->roleWith)();

    expect(app(PurchaseAlerts::class)->targets())->toHaveKeys(['owner', (string) $role->id]);
});
