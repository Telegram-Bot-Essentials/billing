<?php

namespace TelegramBotEssentials\Billing;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use TelegramBotEssentials\Billing\Services\Billing;
use TelegramBotEssentials\Billing\Services\Currency;
use TelegramBotEssentials\Billing\Services\Gateways\Gateways;
use TelegramBotEssentials\Essence\Exceptions\LogicException;

class TbeBillingServiceProvider extends ServiceProvider
{
    /**
     * @throws LogicException
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        $this->initializeSingletons();
        $this->registerPublishing();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'tbe-billing');

        callbackQueryBus()->addCallbackQueries([

        ]);

        stateAnswerBus()->addStateAnswers([

        ]);
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
                __DIR__ . '/../lang' => resource_path('lang/vendor/tbe-billing'),
            ], 'tbe-billing');
        }
    }
}
