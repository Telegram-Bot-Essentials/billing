<?php

namespace TelegramBotEssentials\Essence\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'bot_id' => Bot::first()->id,
            'bot_user_id' => BotUser::first()->id,
            'payable_id' => null,
            'payable_type' => null,
            'price' => 5000,
        ];
    }
}
