<?php

namespace TelegramBotEssentials\UserManagement;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\UserManagement\Telegram\CallbackQueries\Admin\BotUsersQuery;
use TelegramBotEssentials\UserManagement\Telegram\ReplyKeys\Admin\BotUsersKey;
use TelegramBotEssentials\UserManagement\Telegram\StateAnswers\Admin\BotUsersAnswer;

class TbeUserManagementServiceProvider extends ServiceProvider
{
    /**
     * @throws LogicException
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        $this->registerPublishing();

        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'tbe-user-management');

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

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../lang' => resource_path('lang/vendor/tbe-user-management'),
            ], 'tbe-user-management-translations');
        }
    }
}
