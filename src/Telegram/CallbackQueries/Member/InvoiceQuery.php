<?php

namespace TelegramBotEssentials\Billing\Telegram\CallbackQueries\Member;

use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Billing\Models\Attempts\ByWalletAttempt;
use TelegramBotEssentials\Billing\Models\Attempts\ToCardAttempt;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;

class InvoiceQuery extends CallbackQuery
{
    protected string $type = 'INVOICE';
    protected int $perm = Roles::MEMBER->value;

    /**
     * @param array $params
     * @throws BindingResolutionException
     * @throws FeatureIsDisabled
     * @throws LogicException
     * @throws TelegramSDKException
     */
    public function handle(array $params): void
    {
        $this->params = $params;
        switch (strtolower($params[0])) {
            case "to_card":
                dependsOn(wHook()->bot()->settings->pay_with_card);
                dependsOn(isset(wHook()->bot()->settings->pay_to_card_number));
                dependsOn(isset(wHook()->bot()->settings->pay_to_card_name));
                dependsOn(isset(wHook()->bot()->settings->transactions_chat_id));
                $this->toCard();
                break;
            case "by_wallet":
                $this->byWallet();
                break;
        }
    }

    /**
     * @throws LogicException
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     */
    private function toCard(): void
    {
        $invoice = Invoice::findOrFail($this->params[1]);

        $toCardAttempt = ToCardAttempt::create([
            'card_number' => wHook()->bot()->settings->pay_to_card_number,
            'amount' => $invoice->price
        ]);

        billing()->attemptPayment($invoice, $toCardAttempt);

        $text = __('tbe-billing::invoice.to_card.text.user-pay_message', [
            'cardNumber' => wHook()->bot()->settings->pay_to_card_number,
            'cardName' => wHook()->bot()->settings->pay_to_card_name
        ]);

        wHook()->user()->changeState(encodeAnswerState($this->type, "pay_to_card", ["invoice_id" => $invoice->id]));

        $invoice->messageMeta->lockAction(__('tbe-billing::invoice.to_card.lock-keys.user-waiting_for_payment'));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => $text,
            'reply_markup' => wHook()->user()->getKeyboard()
        ]);
        $this->answer(__('tbe-billing::invoice.to_card.answers.attempting'));
    }

    private function byWallet(): void
    {
        $invoice = Invoice::findOrFail($this->params[1]);

        if($invoice->botUser->balance < $invoice->price){
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => __('tbe-billing::invoice.by_wallet.answers.creditIsNotEnough', [
                    'credit' => currency()->priceFormat($invoice->botUser->balance),
                    'neededCredit' => currency()->priceFormat($invoice->price)
                ]),
                'show_alert' => true,
            ]);
            return;
        }

        $byWalletAttempt = ByWalletAttempt::create([
            'amount' => $invoice->price
        ]);

        billing()->attemptPayment($invoice, $byWalletAttempt);

        $byWalletAttempt->attemptSucceed();
        $invoice->messageMeta->lockAction(__('tbe-billing::invoice.to_card.lock-keys.user-payment_accepted'), customEmoji: "✅");
    }
}
