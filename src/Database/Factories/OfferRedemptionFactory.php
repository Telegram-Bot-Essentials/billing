<?php

namespace TelegramBotEssentials\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Models\Offer;
use TelegramBotEssentials\Billing\Models\OfferRedemption;
use TelegramBotEssentials\Essence\Models\BotUser;

class OfferRedemptionFactory extends Factory
{
    protected $model = OfferRedemption::class;

    public function definition(): array
    {
        return [
            'offer_id' => Offer::factory(),
            // Not Invoice::factory(): TelegramBotEssentials\Billing's own
            // InvoiceFactory declares a namespace essence's psr-4 map
            // resolves to, not this package's - a pre-existing bug outside
            // this feature's scope. Build one directly instead.
            'invoice_id' => fn () => Invoice::create([
                'bot_id' => BotUser::first()->bot_id,
                'bot_user_id' => BotUser::first()->id,
                'price' => '0',
                'original_price' => '0',
                // Real class with a bogus id, not a fake string: `payable`
                // is NOT NULL, and MorphTo throws a hard Error for a class
                // name that doesn't exist at all, only null for a real
                // class with no matching row.
                'payable_type' => BotUser::class,
                'payable_id' => 0,
            ])->id,
            'bot_user_id' => BotUser::first()->id,
            'amount_applied' => '500',
        ];
    }
}
