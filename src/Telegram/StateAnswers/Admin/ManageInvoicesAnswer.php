<?php

namespace TelegramBotEssentials\Billing\Telegram\StateAnswers\Admin;

use TelegramBotEssentials\Essence\Enums\AllowableFields;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;

class ManageInvoicesAnswer extends StateAnswer
{
    protected string $type = 'MANAGEINVOICES';
    protected int $perm = Roles::ADMIN->value;
    protected array $allowedFields = [
        AllowableFields::TEXT->value
    ];

//    public function handle(string $method): void
//    {
//        switch (strtolower($method)) {
//
//        }
//    }
}
