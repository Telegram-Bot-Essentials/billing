<?php

namespace TelegramBotEssentials\Billing\Models\Attempts;

use TelegramBotEssentials\Billing\Models\Abstract\PaymentAttempt;

class ToZirgozarAttempt extends PaymentAttempt
{
    protected function attemptSucceedHook(): void
    {
        // TODO: Implement attemptSucceedHook() method.
    }

    protected function attemptFailedHook(): void
    {
        // TODO: Implement attemptFailedHook() method.
    }
}
