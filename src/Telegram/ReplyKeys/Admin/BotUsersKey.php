<?php

namespace TelegramBotEssentials\UserManagement\Telegram\ReplyKeys\Admin;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Telegram\Features\Admin\BotUsersFeature;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;

class BotUsersKey extends ReplyKey
{
    protected string $text = 'Bot Users 👥';
    protected int $perm = Roles::ADMIN->value;
    protected string $response = 'Bot Users executed successfully.';

    public function __construct()
    {
        // Multilingual translations
         $this->text = __('tbe::bot_users.reply_key');
        // $this->response = __('');
    }

    public function handle(): void
    {
        BotUsersFeature::start()->send();
    }
}
