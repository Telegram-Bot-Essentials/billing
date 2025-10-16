<?php

namespace TelegramBotEssentials\Billing\Telegram\ReplyKeys\Admin;

use TelegramBotEssentials\Billing\Telegram\Features\Admin\ManageInvoicesFeature;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;

class ManageInvoicesKey extends ReplyKey
{
    protected string $text;
    protected int $perm = Roles::ADMIN->value;
    protected string $response;

    public function __construct()
    {
        $this->text = __('tbe-billing::manage_invoices.reply.keys.manage_invoices.text');
        $this->response = __('tbe-billing::manage_invoices.reply.keys.manage_invoices.response');
    }

    public function handle(): void
    {
        ManageInvoicesFeature::menu()->send();
    }
}
