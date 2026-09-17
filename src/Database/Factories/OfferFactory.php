<?php

namespace TelegramBotEssentials\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TelegramBotEssentials\Billing\Models\Offer;
use TelegramBotEssentials\Essence\Models\Bot;

class OfferFactory extends Factory
{
    protected $model = Offer::class;

    public function definition(): array
    {
        return [
            'bot_id' => Bot::first()->id,
            'code' => 'OFFER'.$this->faker->unique()->numerify('####'),
            'type' => 'percentage',
            'amount' => '10',
            'is_enabled' => true,
        ];
    }

    public function fixed(string $amount = '5000'): self
    {
        return $this->state(['type' => 'fixed', 'amount' => $amount]);
    }

    public function disabled(): self
    {
        return $this->state(['is_enabled' => false]);
    }

    public function draft(): self
    {
        return $this->state(['type' => null, 'amount' => null, 'is_enabled' => false]);
    }

    public function expired(): self
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }
}
