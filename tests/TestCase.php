<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Billing\Tests;

use TelegramBotEssentials\Billing\TbeBillingServiceProvider;
use TelegramBotEssentials\Essence\Testing\TestCase as EssenceTestCase;
use TelegramBotEssentials\Settings\TbeSettingsServiceProvider;
use TelegramBotEssentials\UserManagement\TbeUserManagementServiceProvider;

abstract class TestCase extends EssenceTestCase
{
    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            TbeSettingsServiceProvider::class,
            TbeUserManagementServiceProvider::class,
            TbeBillingServiceProvider::class,
        ]);
    }
}
