<?php

namespace TelegramBotEssentials\Billing\Telegram\CallbackQueries\Member;

use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Services\OfferService;
use TelegramBotEssentials\Billing\Telegram\Features\Member\InvoiceFeature;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;

class InvoiceQuery extends CallbackQuery
{
    protected string $type = 'INVOICE';

    protected int $perm = Roles::MEMBER->value;

    public function useOfferCode(Invoice $invoice): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->cancelableLockAction(__('tbe-billing::invoice.offer.lockLabel'));

        wHook()->user()->changeState(encodeAnswerState($this->type, 'redeemCode', [
            'invoice' => $invoice->id,
            'message_meta' => $messageMeta->id,
        ]));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-billing::invoice.offer.prompt'),
            'reply_markup' => wHook()->user()->getKeyboard(),
            'parse_mode' => 'HTML',
        ]);

        $this->answer();
    }

    public function removeOfferCode(Invoice $invoice): void
    {
        app(OfferService::class)->remove($invoice);

        InvoiceFeature::invoice($invoice)
            ->answer(__('tbe-billing::invoice.offer.removed'))
            ->update();
    }
}
