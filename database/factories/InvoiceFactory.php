<?php

namespace TelegramBotEssentials\Essence\Database\factories;

use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\CreditOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;
    public function definition(): array
    {
        return [
            'bot_id' => Bot::first()->id,
            'bot_user_id' => BotUser::first()->id,
            'payable_id' => CreditOrder::first()->id,
            'payable_type' => CreditOrder::class,
            'price' => 5000,
        ];
    }
}
