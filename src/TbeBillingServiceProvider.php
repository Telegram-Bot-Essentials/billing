<?php

namespace TelegramBotEssentials\Billing;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use TelegramBotEssentials\Billing\Console\Commands\MarkOverdueInvoicesAsFailed;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Providers\EventServiceProvider;
use TelegramBotEssentials\Billing\Services\Billing;
use TelegramBotEssentials\Billing\Services\Currency;
use TelegramBotEssentials\Billing\Services\Gateways;
use TelegramBotEssentials\Billing\Services\OfferService;
use TelegramBotEssentials\Billing\Telegram\CallbackQueries\Admin\ManageInvoicesQuery;
use TelegramBotEssentials\Billing\Telegram\CallbackQueries\Admin\OffersQuery;
use TelegramBotEssentials\Billing\Telegram\CallbackQueries\Member\InvoiceQuery;
use TelegramBotEssentials\Billing\Telegram\Features\Admin\ManageInvoicesFeature;
use TelegramBotEssentials\Billing\Telegram\Forms\CreateOfferForm;
use TelegramBotEssentials\Billing\Telegram\ReplyKeys\Admin\OffersKey;
use TelegramBotEssentials\Billing\Telegram\StateAnswers\Admin\ManageInvoicesAnswer;
use TelegramBotEssentials\Billing\Telegram\StateAnswers\Admin\OffersAnswer;
use TelegramBotEssentials\Billing\Telegram\StateAnswers\Member\InvoiceAnswer;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Settings\DTOs\Setting;
use TelegramBotEssentials\Settings\Enums\SettingType;
use TelegramBotEssentials\UserManagement\DTOs\BotUserSort;
use TelegramBotEssentials\UserManagement\DTOs\UserSection;
use TelegramBotEssentials\UserManagement\Enums\SectionMode;
use TelegramBotEssentials\UserManagement\Services\BotUserSorts;
use TelegramBotEssentials\UserManagement\Services\UserManagementSections;

class TbeBillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->initializeSingletons();
        $this->registerPublishing();
        $this->app->register(EventServiceProvider::class);

        $this->mergeConfigFrom(__DIR__.'/../config/tbe-billing.php', 'tbe-billing');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'tbe-billing');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MarkOverdueInvoicesAsFailed::class,
            ]);
        }
    }

    private function initializeSingletons(): void
    {
        $this->app->singleton(Billing::class, fn () => new Billing);
        $this->app->singleton(Gateways::class, fn () => new Gateways);
        $this->app->singleton(OfferService::class, fn () => new OfferService);

        // Scoped, not singleton: Currency caches the current bot's currency
        // setting in its constructor. Fine under classic PHP-FPM (container
        // rebuilt every request), but under Octane a singleton would keep
        // formatting every subsequent bot's prices using whichever bot's
        // currency happened to be resolved first.
        $this->app->scoped(Currency::class, fn () => new Currency);

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
                __DIR__.'/../config/tbe-billing.php' => config_path('tbe-billing.php'),
            ], 'tbe-billing-config');

            $this->publishes([
                __DIR__.'/../lang' => resource_path('lang/vendor/tbe-billing'),
            ], 'tbe-billing');
        }
    }

    /**
     * @throws LogicException
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        callbackQueryBus()->addCallbackQueries([
            ManageInvoicesQuery::class,
            OffersQuery::class,
            InvoiceQuery::class,
        ]);

        stateAnswerBus()->addStateAnswers([
            ManageInvoicesAnswer::class,
            OffersAnswer::class,
            InvoiceAnswer::class,
        ]);

        formRegistry()->addForms([
            CreateOfferForm::class,
        ]);

        replyKeyBus()->addReplyKeys([
            OffersKey::class,
        ]);

        $this->addSettings();
        $this->registerUserSection();

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command(MarkOverdueInvoicesAsFailed::class)->hourly();
        });
    }

    /**
     * Optional: user-management is not a dependency of this package. A member's profile in user management gets a button
     * into the invoice list narrowed to that member.
     */
    private function registerUserSection(): void
    {
        if (! class_exists(UserSection::class)) {
            return;
        }

        app(UserManagementSections::class)->addSection(new UserSection(
            key: 'invoices',
            order: 30,
            mode: SectionMode::BUTTON,
            label: fn (BotUser $user) => __('tbe-billing::user_management.section.label', [
                'count' => Invoice::query()->where('bot_user_id', $user->id)->count(),
            ]),
            target: fn (BotUser $user) => encodeCallback(ManageInvoicesFeature::$type, 'user', [$user->id]),
        ));

        // Paid invoices only: pending and failed ones are not money the member has spent.
        app(BotUserSorts::class)->addSort(new BotUserSort(
            key: 'total_paid',
            label: fn () => __('tbe-billing::user_management.sorts.total_paid'),
            apply: fn (Builder $query, string $direction) => $direction === 'asc'
                ? $query->orderBy(self::paidTotal())
                : $query->orderByDesc(self::paidTotal()),
            display: fn (BotUser $user) => currency()->priceFormat((string) Invoice::query()->where('bot_user_id', $user->id)->where('status', 'paid')->sum('price')),
        ));
    }

    /**
     * The sum of the paid invoices of the outer bot_users row, as a subquery.
     *
     * @return Builder<Invoice>
     */
    private static function paidTotal(): Builder
    {
        return Invoice::query()
            ->selectRaw('COALESCE(SUM(price), 0)')
            ->where('status', 'paid')
            ->whereColumn('bot_user_id', 'bot_users.id');
    }

    private function addSettings(): void
    {
        settings()->addSetting(new Setting(
            key: 'billing',
            label: fn () => __('tbe-billing::settings.labels.billing'),
            type: SettingType::DIRECTORY,
            description: fn () => __('tbe-billing::settings.descriptions.billing'),
        ));

        settings()->addSetting(new Setting(
            key: 'billing.gateways',
            label: fn () => __('tbe-billing::settings.labels.gateways'),
            type: SettingType::DIRECTORY,
            description: fn () => __('tbe-billing::settings.descriptions.gateways'),
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
            description: fn () => __('tbe-billing::settings.descriptions.currency'),
        ));
    }
}
