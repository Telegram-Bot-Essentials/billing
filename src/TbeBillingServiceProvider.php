<?php

namespace TelegramBotEssentials\Billing;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use TelegramBotEssentials\Billing\Providers\EventServiceProvider;
use TelegramBotEssentials\Billing\Services\Billing;
use TelegramBotEssentials\Billing\Services\Currency;
use TelegramBotEssentials\Billing\Services\Gateways;
use TelegramBotEssentials\Billing\Telegram\CallbackQueries\Admin\ManageInvoicesQuery;
use TelegramBotEssentials\Billing\Telegram\StateAnswers\Admin\ManageInvoicesAnswer;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Settings\DTOs\Setting;
use TelegramBotEssentials\Settings\Enums\SettingType;

class TbeBillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->initializeSingletons();
        $this->registerPublishing();
        $this->app->register(EventServiceProvider::class);

        $this->mergeConfigFrom(__DIR__ . '/../config/tbe-billing.php', 'tbe-billing');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'tbe-billing');
    }

    private function initializeSingletons(): void
    {
        $this->app->singleton(Billing::class, fn() => new Billing());
        $this->app->singleton(Gateways::class, fn() => new Gateways());
        $this->app->singleton(Currency::class, fn() => new Currency());

        $this->initializeGatewaySingletons();
    }

    private function initializeGatewaySingletons(): void
    {
//        $this->app->singleton(Gateways::class, function ($app) {
//            return new Gateways();
//        });
//
//        $this->app->singleton(Zibal::class, function ($app) {
//            return new Zibal();
//        });
//
//        $this->app->singleton(ZarinPal::class, function ($app) {
//            return new ZarinPal();
//        });
//
//        $this->app->singleton(Wallet::class, function () {
//            return new Wallet();
//        });
    }

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/tbe-billing.php' => config_path('tbe-billing.php'),
            ], 'tbe-billing-config');

            $this->publishes([
                __DIR__ . '/../lang' => resource_path('lang/vendor/tbe-billing'),
            ], 'tbe-billing');
        }
    }

    /**
     * @throws LogicException
     * @throws BindingResolutionException
     */
    function boot(): void
    {
        callbackQueryBus()->addCallbackQueries([
            ManageInvoicesQuery::class
        ]);

        stateAnswerBus()->addStateAnswers([
            ManageInvoicesAnswer::class
        ]);

        $this->addSettings();
    }

    private function addSettings(): void
    {
        settings()->addSetting(new Setting(
            key: 'billing',
            label: 'Billing',
            type: SettingType::DIRECTORY,
        ));

        settings()->addSetting(new Setting(
            key: 'billing.gateways',
            label: 'Gateways',
            type: SettingType::DIRECTORY,
        ));

        settings()->addSetting(new Setting(
            key: 'billing.currency',
            label: 'Currency',
            type: SettingType::ENUM,
            default: 'USD',
            options: collect(config('tbe-billing.supported_currencies', []))->pluck('name')->toArray()
        ));
    }
}
