<?php

namespace TelegramBotEssentials\Billing\Telegram\StateAnswers\Admin;

use TelegramBotEssentials\Billing\Models\Offer;
use TelegramBotEssentials\Billing\Services\OfferService;
use TelegramBotEssentials\Billing\Telegram\Features\Admin\OffersFeature;
use TelegramBotEssentials\Essence\Enums\AllowableFields;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
use TelegramBotEssentials\Essence\Services\TelegramPaginator;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;

class OffersAnswer extends StateAnswer
{
    protected string $type = 'OFFERS';

    protected int $perm = Roles::ADMIN->value;

    protected array $allowedFields = [
        AllowableFields::TEXT->value,
    ];

    /**
     * @throws InvalidPageNumber
     */
    public function setStartPage(): void
    {
        $page = wHook()->update()->message->text;
        $lastPage = Offer::query()->where('bot_id', wHook()->bot()->id)->paginate(perPage: 10)->lastPage();

        TelegramPaginator::validatePageInput($page, $lastPage);

        $data = OffersFeature::menu(intval($page));

        wHook()->user()->changeState();
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-billing::offers.wizard.pageLoaded', ['page' => $page]),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $this->requireMessageMeta()->updateAndContinueAction($data);
    }

    public function updateField(Offer $offer, string $field, int $lastPage): void
    {
        $text = trim(wHook()->update()->message->text);

        if ($field === 'amount') {
            app(OfferService::class)->assignAmount($offer, $text);
        } else {
            app(OfferService::class)->assignField($offer, $field, $text);
        }

        $offer->save();
        wHook()->user()->changeState();

        $this->requireMessageMeta()->updateAndContinueAction(OffersFeature::show($offer, $lastPage));
    }
}
