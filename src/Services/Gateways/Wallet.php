<?php

namespace TelegramBotEssentials\Billing\Services\Gateways;


use Brick\Math\BigDecimal;
use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicExceptions\InsufficientBalanceException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class Wallet
{
    /**
     * @param BigDecimal|string $amount
     * @throws FeatureIsDisabled
     * @throws TbeLogicException
     * @throws TelegramSDKException
     * @throws LogicException
     * @throws BindingResolutionException
     */
    public function takeAmount(BigDecimal|string $amount): void
    {
        $this->validateAmount($amount);
        $this->validateMethodAllowed();
        $this->validateUserBalanceIsSufficient($amount);

        wHook()->user()->balance = $this->currentUserBalance()->minus($amount);
        wHook()->user()->save();

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => currency()->priceFormat($amount) . " 💸 successfully taken from your wallet",
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }

    private function validateAmount(BigDecimal|string &$amount): void
    {
        if (!($amount instanceof BigDecimal)) {
            $amount = BigDecimal::of($amount);
        }
    }

    /**
     * @throws FeatureIsDisabled
     */
    public function validateMethodAllowed(): void
    {
        dependsOn(wHook()->bot()->settings->wallet, __('tbe::general.alerts.disabledFeature', ['feature' => __('tbe::bot_settings.wallet.name')]));
    }

    /**
     * @throws InsufficientBalanceException
     */
    private function validateUserBalanceIsSufficient(BigDecimal|string $amount): void
    {
        $this->validateAmount($amount);
        if (BigDecimal::of($amount)->compareTo($this->currentUserBalance()) > 0) {
            throw new InsufficientBalanceException(__('tbe::invoice.by_wallet.answers.creditIsNotEnough', [
                'credit' => currency()->priceFormat($this->currentUserBalance()),
                'neededCredit' => currency()->priceFormat($amount)
            ]));
        }
    }

    public function currentUserBalance(): BigDecimal
    {
        return BigDecimal::of(wHook()->user()->balance);
    }

    /**
     * @param BigDecimal|string $amount
     * @throws FeatureIsDisabled
     * @throws TbeLogicException
     * @throws TelegramSDKException
     * @throws LogicException
     * @throws BindingResolutionException
     */
    public function addAmount(BigDecimal|string $amount): void
    {
        $this->validateAmount($amount);
        $this->validateMethodAllowed();
        $this->validateUserBalanceIsSufficient($amount);

        wHook()->user()->balance = $this->currentUserBalance()->plus($amount);
        wHook()->user()->save();

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => currency()->priceFormat($amount) . " 💸 successfully added to your wallet",
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }

    public function setAmount(BigDecimal|string $amount): void
    {
        $this->validateAmount($amount);
        $this->validateMethodAllowed();

        wHook()->user()->balance = $amount;
        wHook()->user()->save();

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => "Your total credit set to " . currency()->priceFormat($amount) . " 💸",
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }
}
