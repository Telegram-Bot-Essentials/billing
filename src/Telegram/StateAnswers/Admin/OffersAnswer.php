<?php

namespace TelegramBotEssentials\Billing\Telegram\StateAnswers\Admin;

use Illuminate\Validation\ValidationException;
use TelegramBotEssentials\Billing\Models\Offer;
use TelegramBotEssentials\Billing\Services\OfferService;
use TelegramBotEssentials\Billing\Telegram\Features\Admin\OffersFeature;
use TelegramBotEssentials\Billing\Telegram\Support\OffersWizard;
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
        $lastPage = Offer::query()->where('bot_id', wHook()->bot()->id)->whereNotNull('type')->paginate(perPage: 10)->lastPage();

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

    /**
     * @throws ValidationException
     */
    public function captureCode(int $lastPage): void
    {
        $code = trim(wHook()->update()->message->text);

        if ($code === '') {
            throw ValidationException::withMessages([
                'code' => __('tbe-billing::offers.wizard.errors.codeRequired'),
            ]);
        }

        if (app(OfferService::class)->isCodeTaken(wHook()->bot()->id, $code)) {
            throw ValidationException::withMessages([
                'code' => __('tbe-billing::offers.wizard.errors.codeTaken'),
            ]);
        }

        $offer = Offer::create([
            'bot_id' => wHook()->bot()->id,
            'code' => $code,
        ]);

        $messageMetaId = $this->requireMessageMeta()->id;
        wHook()->user()->changeState();

        OffersFeature::choosingType($offer, $lastPage, $messageMetaId)->send();
    }

    public function captureAmount(Offer $offer, int $lastPage): void
    {
        app(OfferService::class)->assignAmount($offer, trim(wHook()->update()->message->text));
        $offer->save();

        OffersWizard::advance($offer, null, $lastPage, $this->requireMessageMeta());
    }

    public function captureOptionalField(Offer $offer, string $field, int $lastPage): void
    {
        app(OfferService::class)->assignField($offer, $field, trim(wHook()->update()->message->text));
        $offer->save();

        OffersWizard::advance($offer, $field, $lastPage, $this->requireMessageMeta());
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

    /**
     * A wizard abandoned before the offer is fully built (is_enabled never
     * flips true) leaves a draft row behind - clean it up rather than
     * leaving a dead code lying around the admin's list.
     */
    public function cancel(): void
    {
        $offerId = $this->params['offer'] ?? null;

        if ($offerId) {
            $offer = Offer::find($offerId);

            if ($offer && ! $offer->is_enabled) {
                $offer->forceDelete();
            }
        }

        parent::cancel();
    }
}
