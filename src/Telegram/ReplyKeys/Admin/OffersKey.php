<?php

namespace TelegramBotEssentials\Billing\Telegram\ReplyKeys\Admin;

use TelegramBotEssentials\Billing\Telegram\Features\Admin\OffersFeature;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;

class OffersKey extends ReplyKey
{
    protected int $perm = Roles::ADMIN->value;

    protected function text(): string
    {
        return __('tbe-billing::offers.reply.keys.offers.text');
    }

    protected function response(): string
    {
        return __('tbe-billing::offers.reply.keys.offers.response');
    }

    public function handle(): void
    {
        OffersFeature::menu()->send();
    }
}
