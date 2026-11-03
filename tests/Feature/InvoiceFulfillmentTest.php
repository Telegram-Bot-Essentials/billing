<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Telegram\Features\Admin\ManageInvoicesFeature;

/*
 * A paid invoice stays paid, whatever becomes of delivering it: the failure is
 * recorded, people are told, and it can be tried again.
 */

class FulfillmentTestOrder extends Model
{
    protected $table = 'fulfillment_test_orders';

    public $timestamps = false;

    protected $guarded = [];

    public static bool $fails = false;

    public static int $hookRuns = 0;

    public function invoicePaidHook(): void
    {
        self::$hookRuns++;

        if (self::$fails) {
            throw new RuntimeException('panel is down');
        }
    }
}

beforeEach(function () {
    Schema::create('fulfillment_test_orders', fn ($table) => $table->id());
    FulfillmentTestOrder::$fails = false;
    FulfillmentTestOrder::$hookRuns = 0;
    config(['queue.default' => 'sync']);

    $this->bot = $this->makeBot();
    $this->member = $this->makeBotUser($this->bot, 7301);

    tenancy()->initialize($this->bot);
    wHook()->setBot($this->bot);
    wHook()->setUser($this->member);
    wHook()->setApi(telegramApi((string) $this->bot->bot_token));
    wHook()->setUpdate(new Update(['update_id' => 1]));

    $order = FulfillmentTestOrder::create();
    $this->invoice = Invoice::create([
        'bot_id' => $this->bot->id,
        'bot_user_id' => $this->member->id,
        'payable_type' => FulfillmentTestOrder::class,
        'payable_id' => $order->id,
        'price' => 5000,
    ]);
});

function messagesTo(int $peerId): array
{
    return Http::recorded(fn ($request) => str_ends_with((string) $request->url(), '/sendMessage') && (int) $request['chat_id'] === $peerId)
        ->map(fn ($pair) => (string) $pair[0]['text'])
        ->values()
        ->all();
}

it('records delivery of a paid invoice', function () {
    $this->invoice->markAsPaid();

    $invoice = $this->invoice->fresh();
    expect($invoice->status)->toBe('paid')
        ->and($invoice->needsFulfillment())->toBeFalse()
        ->and($invoice->fulfilled_at)->not->toBeNull();
});

it('keeps the invoice paid and the reason when delivery fails, without throwing', function () {
    FulfillmentTestOrder::$fails = true;

    $this->invoice->markAsPaid();

    $invoice = $this->invoice->fresh();
    expect($invoice->status)->toBe('paid')
        ->and($invoice->needsFulfillment())->toBeTrue()
        ->and($invoice->fulfillment_error)->toBe('panel is down');
});

it('tells the owner and the member when delivery fails', function () {
    FulfillmentTestOrder::$fails = true;

    $this->invoice->markAsPaid();

    expect(messagesTo(7301))->toContain(__('tbe-billing::invoice.hooks.fulfillment_failed'));
    expect(implode("\n", messagesTo((int) $this->bot->bot_owner_peer_id)))->toContain('panel is down');
});

it('alerts the owner about every failed order, not one per window', function () {
    FulfillmentTestOrder::$fails = true;
    $second = Invoice::create([
        'bot_id' => $this->bot->id,
        'bot_user_id' => $this->member->id,
        'payable_type' => FulfillmentTestOrder::class,
        'payable_id' => FulfillmentTestOrder::create()->id,
        'price' => 5000,
    ]);

    $this->invoice->markAsPaid();
    $second->markAsPaid();

    expect(collect(messagesTo((int) $this->bot->bot_owner_peer_id))->filter(fn ($text) => str_contains($text, 'panel is down')))->toHaveCount(2);
});

it('tells nobody about delay when the payment around it rolls back', function () {
    FulfillmentTestOrder::$fails = true;

    try {
        DB::transaction(function () {
            $this->invoice->markAsPaid();
            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
    }

    expect(messagesTo(7301))->not->toContain(__('tbe-billing::invoice.hooks.fulfillment_failed'));
});

it('delivers on retry and clears the reason', function () {
    FulfillmentTestOrder::$fails = true;
    $this->invoice->markAsPaid();

    FulfillmentTestOrder::$fails = false;
    $done = $this->invoice->fresh()->fulfill();

    $invoice = $this->invoice->fresh();
    expect($done)->toBeTrue()
        ->and($invoice->needsFulfillment())->toBeFalse()
        ->and($invoice->fulfillment_error)->toBeNull();
});

it('shows admins a retry button only on an invoice waiting for delivery', function () {
    $keys = fn (Invoice $invoice) => collect(ManageInvoicesFeature::show($invoice)->replyMarkup->toArray()['inline_keyboard'])
        ->flatten(1)->pluck('text');

    $this->invoice->markAsPaid();
    expect($keys($this->invoice->fresh()))->not->toContain(__('tbe-billing::manage_invoices.main.keys.retry_fulfillment'));

    Invoice::query()->whereKey($this->invoice->id)->update(['fulfilled_at' => null]);
    expect($keys($this->invoice->fresh()))->toContain(__('tbe-billing::manage_invoices.main.keys.retry_fulfillment'));
});

it('takes invoices paid before this was tracked as delivered', function () {
    $invoice = $this->invoice;
    Invoice::query()->whereKey($invoice->id)->update(['status' => 'paid', 'fulfilled_at' => null]);

    (require __DIR__.'/../../database/migrations/2026_11_03_120000_add_fulfillment_columns_to_invoices_table.php')->down();
    (require __DIR__.'/../../database/migrations/2026_11_03_120000_add_fulfillment_columns_to_invoices_table.php')->up();

    expect($invoice->fresh()->needsFulfillment())->toBeFalse();
});

it('holds whatever telegram does', function () {
    $calls = $this->sweepTelegramFailures(function () {
        tenancy()->initialize($this->bot);
        wHook()->setBot($this->bot);
        wHook()->setUser($this->member);
        wHook()->setApi(telegramApi((string) $this->bot->bot_token));
        wHook()->setUpdate(new Update(['update_id' => 1]));

        $this->invoice->fresh()->markAsPaid();
    }, function (int $failed) {
        $invoice = $this->invoice->fresh();

        expect($invoice->status)->toBe('paid', "call {$failed}")
            ->and($invoice->needsFulfillment())->toBeFalse("call {$failed}");
    });

    expect($calls)->toBeGreaterThan(0);
});
