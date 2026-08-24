<?php

namespace TelegramBotEssentials\Billing\Telegram\ReplyKeys\Admin;

use TelegramBotEssentials\Billing\Telegram\Features\Admin\ManageInvoicesFeature;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;

class ManageInvoicesKey extends ReplyKey
{
    protected string $textKey = 'tbe-billing::manage_invoices.reply.keys.manage_invoices.text';
    protected int $perm = Roles::ADMIN->value;
    protected string $responseKey = 'tbe-billing::manage_invoices.reply.keys.manage_invoices.response';


    public function handle(): void
    {
        ManageInvoicesFeature::menu()->send();
    }
}
