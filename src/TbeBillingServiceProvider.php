<?php

namespace TelegramBotEssentials\Billing;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use TelegramBotEssentials\Billing\Console\Commands\MarkOverdueInvoicesAsFailed;
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

        if ($this->app->runningInConsole()) {
            $this->commands([
                MarkOverdueInvoicesAsFailed::class,
            ]);
        }
    }

    private function initializeSingletons(): void
    {
        $this->app->singleton(Billing::class, fn() => new Billing());
        $this->app->singleton(Gateways::class, fn() => new Gateways());

        // Scoped, not singleton: Currency caches the current bot's currency
        // setting in its constructor. Fine under classic PHP-FPM (container
        // rebuilt every request), but under Octane a singleton would keep
        // formatting every subsequent bot's prices using whichever bot's
        // currency happened to be resolved first.
        $this->app->scoped(Currency::class, fn() => new Currency());

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

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command(MarkOverdueInvoicesAsFailed::class)->hourly();
        });
    }

    private function addSettings(): void
    {
        settings()->addSetting(new Setting(
            key: 'billing',
            label: fn () => __('tbe-billing::settings.labels.billing'),
            type: SettingType::DIRECTORY,
        ));

        settings()->addSetting(new Setting(
            key: 'billing.gateways',
            label: fn () => __('tbe-billing::settings.labels.gateways'),
            type: SettingType::DIRECTORY,
        ));

        settings()->addSetting(new Setting(
            key: 'billing.currency',
            label: fn () => __('tbe-billing::settings.labels.currency'),
            type: SettingType::ENUM,
            default: 'USD',
            options: fn () => array_merge(
                collect(config('tbe-billing.supported_currencies', []))->pluck('name')->toArray(),
                ['USD']
            ),
        ));
    }
}
