<?php

namespace TelegramBotEssentials\Billing\Telegram\CallbackQueries\Admin;

use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Models\Billing\Attempts\ToCardAttempt;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;

class ManageInvoiceQuery extends CallbackQuery
{
    protected string $type = 'MANAGE_INVOICE';
    protected int $perm = Roles::ADMIN->value;

    /**
     * @param array $params
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException
     */
    public function handle(array $params): void
    {
        $this->params = $params;
        switch (strtolower($params[0])) {
            case "accept_card_payment":
                $this->acceptCardPayment();
                break;
            case "reject_card_payment":
                $this->rejectCardPayment();
                break;
        }
    }

    private function acceptCardPayment(): void
    {
        $toCardAttempt = ToCardAttempt::findOrFail($this->params[1]);

        $toCardAttempt->attemptSucceed();
        $invoice = $toCardAttempt->invoice;
        $invoice->messageMeta->lockAction(__('tbe::invoice.to_card.lock-keys.user-payment_accepted'), customEmoji: "✅");
        $toCardAttempt->messageMeta->lockAction(__('tbe::invoice.to_card.lock-keys.admin-payment_accepted_by', [
            'adminName' => wHook()->user()->telegramUser->full_name]), customEmoji: "✅");
        $this->answer(__('tbe::invoice.to_card.answers.admin-payment_accepted'));
    }

    /**
     * @throws LogicException
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     */
    private function rejectCardPayment(): void
    {
        $toCardAttempt = ToCardAttempt::findOrFail($this->params[1]);
        wHook()->user()->changeState(encodeAnswerState($this->type, "reject_reason", ["to_card_attempt_id" => $toCardAttempt->id]));
        $toCardAttempt->messageMeta->lockAction(__('tbe::invoice.to_card.lock-keys.admin-rejecting_payment'));

        $text = __('tbe::invoice.to_card.text.admin_payment_rejection', [
            'toCardAttemptId' => $toCardAttempt->id,
        ]);

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $text,
            'reply_markup' => wHook()->user()->getKeyboard(),
            'reply_to_message_id' => $toCardAttempt->messageMeta->message_id,
        ]);
        $this->answer(__('tbe::invoice.to_card.answers.admin-rejecting_payment'));
    }
}
