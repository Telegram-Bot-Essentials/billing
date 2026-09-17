<?php

namespace TelegramBotEssentials\Billing\Telegram\Support;

use TelegramBotEssentials\Billing\Models\Offer;
use TelegramBotEssentials\Billing\Services\OfferService;
use TelegramBotEssentials\Billing\Telegram\Features\Admin\OffersFeature;
use TelegramBotEssentials\Essence\Models\MessageMeta;

/**
 * Shared "what happens after this optional wizard step" logic, called from
 * both the text-answer path (a value was typed) and the callback path (Skip
 * was tapped) so the two don't duplicate the advance-or-finish chain.
 */
class OffersWizard
{
    public static function advance(Offer $offer, ?string $justCompleted, int $lastPage, MessageMeta $messageMeta): void
    {
        $next = app(OfferService::class)->nextOptionalField($offer, $justCompleted);

        if ($next === null) {
            self::finish($offer, $lastPage, $messageMeta);

            return;
        }

        wHook()->user()->changeState(encodeAnswerState(OffersFeature::$type, 'captureOptionalField', [
            'offer' => $offer->id,
            'field' => $next,
            'lastPage' => $lastPage,
            'message_meta' => $messageMeta->id,
        ]));

        OffersFeature::optionalFieldPrompt($offer, $next, $lastPage, $messageMeta->id)->send();
    }

    private static function finish(Offer $offer, int $lastPage, MessageMeta $messageMeta): void
    {
        $offer->update(['is_enabled' => true]);
        wHook()->user()->changeState();

        $messageMeta->updateAndContinueAction(OffersFeature::show($offer, $lastPage));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-billing::offers.wizard.finished', ['code' => $offer->code]),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }
}
