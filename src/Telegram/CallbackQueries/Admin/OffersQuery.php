<?php

namespace TelegramBotEssentials\Billing\Telegram\CallbackQueries\Admin;

use Illuminate\Validation\ValidationException;
use TelegramBotEssentials\Billing\Models\Offer;
use TelegramBotEssentials\Billing\Services\OfferService;
use TelegramBotEssentials\Billing\Telegram\Features\Admin\OffersFeature;
use TelegramBotEssentials\Billing\Telegram\Support\OffersWizard;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;

class OffersQuery extends CallbackQuery
{
    protected string $type = 'OFFERS';

    protected int $perm = Roles::ADMIN->value;

    /**
     * @throws InvalidPageNumber
     */
    public function start(int $page = 1, int $currentPage = 0): void
    {
        OffersFeature::menu($page, $currentPage)->update();
    }

    public function setStartPage(): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->lockAction(__('tbe-billing::offers.wizard.waitingPage'));

        wHook()->user()->changeState(encodeAnswerState($this->type, 'setStartPage', [
            'message_meta' => $messageMeta->id,
        ]));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-billing::offers.wizard.enterPage'),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $this->answer();
    }

    public function show(Offer $offer, int $lastPage = 1): void
    {
        OffersFeature::show($offer, $lastPage)->update();
    }

    public function stats(Offer $offer, int $lastPage = 1): void
    {
        OffersFeature::stats($offer, $lastPage)->update();
    }

    public function toggle(Offer $offer, int $lastPage = 1): void
    {
        $offer->update(['is_enabled' => ! $offer->is_enabled]);

        OffersFeature::show($offer, $lastPage)
            ->answer($offer->is_enabled
                ? __('tbe-billing::offers.main.answers.enabled')
                : __('tbe-billing::offers.main.answers.disabled'))
            ->update();
    }

    /**
     * @throws ValidationException
     */
    public function delete(Offer $offer, int $lastPage = 1): void
    {
        if ($offer->redemptions()->exists()) {
            throw ValidationException::withMessages([
                'offer' => __('tbe-billing::offers.alerts.cannotDeleteHasRedemptions'),
            ]);
        }

        $offer->delete();

        OffersFeature::menu(max(1, $lastPage))
            ->answer(__('tbe-billing::offers.main.answers.deleted'))
            ->update();
    }

    /** Starts the creation wizard: the current screen freezes until the last step. */
    public function create(int $lastPage = 1): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->cancelableLockAction(__('tbe-billing::offers.wizard.lockLabel'));

        wHook()->user()->changeState(encodeAnswerState($this->type, 'captureCode', [
            'lastPage' => $lastPage,
            'message_meta' => $messageMeta->id,
        ]));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-billing::offers.wizard.fields.code.prompt'),
            'reply_markup' => wHook()->user()->getKeyboard(),
            'parse_mode' => 'HTML',
        ]);

        $this->answer();
    }

    public function setType(Offer $offer, string $type, int $lastPage = 1, int $messageMeta = 0): void
    {
        if (! in_array($type, ['percentage', 'fixed'], true)) {
            $this->answer();

            return;
        }

        $offer->update(['type' => $type]);

        wHook()->user()->changeState(encodeAnswerState($this->type, 'captureAmount', [
            'offer' => $offer->id,
            'lastPage' => $lastPage,
            'message_meta' => $messageMeta,
        ]));

        wHook()->api()->editMessageText([
            'chat_id' => wHook()->update()->callbackQuery->message->chat->id,
            'message_id' => wHook()->update()->callbackQuery->message->messageId,
            'text' => __('tbe-billing::offers.wizard.chooseType.chosen', [
                'type' => __('tbe-billing::offers.main.type.'.$type),
            ]),
            'parse_mode' => 'HTML',
        ]);

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-billing::offers.wizard.fields.amount.prompt.'.$type),
            'reply_markup' => wHook()->user()->getKeyboard(),
            'parse_mode' => 'HTML',
        ]);

        $this->answer();
    }

    public function skipOptionalField(Offer $offer, string $field, int $lastPage = 1, int $messageMeta = 0): void
    {
        app(OfferService::class)->clearField($offer, $field);
        $offer->save();

        OffersWizard::advance($offer, $field, $lastPage, MessageMeta::findOrFail($messageMeta));

        $this->answer();
    }

    public function edit(Offer $offer, string $field, int $lastPage = 1): void
    {
        if (! in_array($field, OffersFeature::EDITABLE_FIELDS, true)) {
            $this->answer();

            return;
        }

        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->cancelableLockAction(__('tbe-billing::offers.wizard.fields.'.$field.'.label'));

        wHook()->user()->changeState(encodeAnswerState($this->type, 'updateField', [
            'offer' => $offer->id,
            'field' => $field,
            'lastPage' => $lastPage,
            'message_meta' => $messageMeta->id,
        ]));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => $field === 'amount'
                ? __('tbe-billing::offers.wizard.fields.amount.prompt.'.$offer->type)
                : __('tbe-billing::offers.wizard.fields.'.$field.'.prompt'),
            'reply_markup' => wHook()->user()->getKeyboard(),
            'parse_mode' => 'HTML',
        ]);

        $this->answer();
    }

    public function clearField(Offer $offer, string $field, int $lastPage = 1): void
    {
        if (! in_array($field, OffersFeature::EDITABLE_FIELDS, true)) {
            $this->answer();

            return;
        }

        app(OfferService::class)->clearField($offer, $field);
        $offer->save();

        OffersFeature::show($offer, $lastPage)
            ->answer(__('tbe-billing::offers.main.answers.updated'))
            ->update();
    }
}
