<?php

namespace TelegramBotEssentials\UserManagement;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\UserWallet\Telegram\CallbackQueries\Admin\BotUsersQuery;
use TelegramBotEssentials\UserWallet\Telegram\ReplyKeys\Admin\BotUsersKey;
use TelegramBotEssentials\UserWallet\Telegram\StateAnswers\Admin\BotUsersAnswer;

class TbeUserWalletServiceProvider extends ServiceProvider
{
    /**
     * @throws LogicException
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        replyKeyBus()->addReplyKeys([
            BotUsersKey::class
        ]);

        callbackQueryBus()->addCallbackQueries([
            BotUsersQuery::class
        ]);

        stateAnswerBus()->addStateAnswers([
            BotUsersAnswer::class
        ]);
    }
}
