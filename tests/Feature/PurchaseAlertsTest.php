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
use TelegramBotEssentials\Settings\Telegram\Features\Admin\BotSettingsFeature;

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
        $role = Role::create(['bot_id' => $this->bot->id, 'name' => 'Sales '.Role::count(), 'abilities' => []]);

        foreach ($peers as $peer) {
            // The owner already has a telegram user, from makeBot().
            $user = $peer === (int) $this->bot->bot_owner_peer_id
                ? BotUser::factory()->create(['bot_id' => $this->bot->id, 'telegram_user_peer_id' => $peer, 'suspend' => false])
                : $this->makeBotUser($this->bot, $peer);

            $role->users()->attach($user->id);
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
    settings()->set('billing.purchase_alerts.targets', [(string) $role->id]);

    ($this->paidInvoice)();

    expect(($this->alertsTo)(STAFF_PEER))->toBe(1)
        ->and(($this->alertsTo)(OTHER_STAFF_PEER))->toBe(1)
        ->and(($this->alertsTo)((int) $this->bot->bot_owner_peer_id))->toBe(0);
});

it('falls back to the owner when nobody holds the role', function () {
    $role = ($this->roleWith)();
    settings()->set('billing.purchase_alerts.status', true);
    settings()->set('billing.purchase_alerts.targets', [(string) $role->id]);

    ($this->paidInvoice)();

    expect(($this->alertsTo)((int) $this->bot->bot_owner_peer_id))->toBe(1);
});

it('falls back to the owner when the role was deleted', function () {
    $role = ($this->roleWith)(STAFF_PEER);
    settings()->set('billing.purchase_alerts.status', true);
    settings()->set('billing.purchase_alerts.targets', [(string) $role->id]);
    $role->delete();

    ($this->paidInvoice)();

    expect(($this->alertsTo)((int) $this->bot->bot_owner_peer_id))->toBe(1);
});

it('reaches the other staff when one has blocked the bot', function () {
    $role = ($this->roleWith)(STAFF_PEER, OTHER_STAFF_PEER);
    settings()->set('billing.purchase_alerts.status', true);
    settings()->set('billing.purchase_alerts.targets', [(string) $role->id]);

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

it('reaches the owner and several roles together', function () {
    $sales = ($this->roleWith)(STAFF_PEER);
    $support = ($this->roleWith)(OTHER_STAFF_PEER);
    settings()->set('billing.purchase_alerts.status', true);
    settings()->set('billing.purchase_alerts.targets', ['owner', (string) $sales->id, (string) $support->id]);

    ($this->paidInvoice)();

    expect(($this->alertsTo)((int) $this->bot->bot_owner_peer_id))->toBe(1)
        ->and(($this->alertsTo)(STAFF_PEER))->toBe(1)
        ->and(($this->alertsTo)(OTHER_STAFF_PEER))->toBe(1);
});

it('sends one message to someone holding two targeted roles', function () {
    $sales = ($this->roleWith)(STAFF_PEER);
    $support = ($this->roleWith)();
    $support->users()->attach($sales->users()->first()->id);
    settings()->set('billing.purchase_alerts.status', true);
    settings()->set('billing.purchase_alerts.targets', [(string) $sales->id, (string) $support->id]);

    ($this->paidInvoice)();

    expect(($this->alertsTo)(STAFF_PEER))->toBe(1);
});

it('sends one message to an owner who is targeted and holds the role', function () {
    $owner = (int) $this->bot->bot_owner_peer_id;
    $role = ($this->roleWith)($owner);
    settings()->set('billing.purchase_alerts.status', true);
    settings()->set('billing.purchase_alerts.targets', ['owner', (string) $role->id]);

    ($this->paidInvoice)();

    expect(($this->alertsTo)($owner))->toBe(1);
});

it('reaches an owner holding a targeted role when the owner is not picked', function () {
    $owner = (int) $this->bot->bot_owner_peer_id;
    $role = ($this->roleWith)($owner);
    settings()->set('billing.purchase_alerts.status', true);
    settings()->set('billing.purchase_alerts.targets', [(string) $role->id]);

    ($this->paidInvoice)();

    expect(($this->alertsTo)($owner))->toBe(1);
});

it('fits the longest picker button in telegram\'s callback limit', function () {
    expect(strlen(encodeCallback('BTSTNG', 'multiSelect', ['billing.purchase_alerts.targets', 99999999, true])))->toBeLessThanOrEqual(64);
});

it('renders the target picker and remembers choices across a rename', function () {
    $role = ($this->roleWith)();
    $setting = settings()->getSetting('billing.purchase_alerts.targets');

    $keyboard = fn () => collect(BotSettingsFeature::multiselect($setting)->replyMarkup->toArray()['inline_keyboard'])
        ->flatten(1)->pluck('text')->all();

    settings()->set('billing.purchase_alerts.targets', [(string) $role->id]);
    $role->update(['name' => 'Renamed']);

    expect($keyboard())->toContain('Renamed ✅');
});

it('carries a target chosen before several were allowed over to the new setting', function () {
    DB::table('bot_settings')->insert(['bot_id' => $this->bot->id, 'key' => 'billing.purchase_alerts.target', 'value' => '7']);

    (require __DIR__.'/../../database/migrations/2026_11_03_130000_rename_purchase_alert_target_setting.php')->up();

    expect(DB::table('bot_settings')->where('key', 'billing.purchase_alerts.targets')->value('value'))->toBe('7')
        ->and(DB::table('bot_settings')->where('key', 'billing.purchase_alerts.target')->exists())->toBeFalse();
});
