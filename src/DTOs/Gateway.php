<?php

namespace TelegramBotEssentials\Billing\DTOs;

use Closure;
use Telegram\Bot\Keyboard\Button;
use TelegramBotEssentials\Billing\Models\Invoice;

class Gateway
{
    public function __construct(
        public string $key,
        public string $label,
        public Closure $inlineButtonGenerator,
    ) {}

    public function getInlineKeyboard(Invoice $invoice): Button|array|string|null
    {
        return ($this->inlineButtonGenerator)($invoice);
    }
}
