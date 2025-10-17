<?php

namespace TelegramBotEssentials\Billing\DTOs;

use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Support\Webhook;

class WebhookContext
{
    public function __construct(
        public readonly int $botId,
        public readonly int $botUserId,
        public readonly array $updatePayload = [],
    ) {
    }

    public static function capture(): ?self
    {
        if (!function_exists('wHook')) {
            return null;
        }

        /** @var Webhook $webhook */
        $webhook = wHook();

        try {
            if (!method_exists($webhook, 'check') || !$webhook::check()) {
                return null;
            }

            $bot = $webhook::bot();
            $botUser = $webhook::user();
            $update = $webhook::update();

            return new self(
                botId: $bot->getKey(),
                botUserId: $botUser->getKey(),
                updatePayload: method_exists($update, 'toArray') ? $update->toArray() : [],
            );
        } catch (\Throwable) {
            return null;
        }
    }

    public function resolveBot(): ?Bot
    {
        return Bot::find($this->botId);
    }

    public function resolveBotUser(): ?BotUser
    {
        return BotUser::find($this->botUserId);
    }

    public function resolveUpdate(): Update
    {
        return new Update($this->updatePayload);
    }
}

