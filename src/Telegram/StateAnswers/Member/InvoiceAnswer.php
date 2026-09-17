<?php

namespace TelegramBotEssentials\Billing\Telegram\StateAnswers\Member;

use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Services\OfferService;
use TelegramBotEssentials\Billing\Telegram\Features\Member\InvoiceFeature;
use TelegramBotEssentials\Essence\Enums\AllowableFields;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;

class InvoiceAnswer extends StateAnswer
{
    protected string $type = 'INVOICE';

    protected int $perm = Roles::MEMBER->value;

    protected array $allowedFields = [
        AllowableFields::TEXT->value,
    ];

    /**
     * Validation failures (code not found, expired, min/max, exhausted...)
     * throw a ValidationException that the framework's central handler turns
     * into a chat reply without touching the current state - the user stays
     * on this same prompt and can just try another code.
     */
    public function redeemCode(Invoice $invoice): void
    {
        app(OfferService::class)->redeem($invoice, trim(wHook()->update()->message->text));

        wHook()->user()->changeState();

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-billing::invoice.offer.applied'),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $this->requireMessageMeta()->updateAndContinueAction(InvoiceFeature::invoice($invoice));
    }
}
