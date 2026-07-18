<?php

namespace TelegramBotEssentials\Billing\Telegram\CallbackQueries\Admin;

use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Billing\Telegram\Features\Admin\ManageInvoicesFeature;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;

class ManageInvoicesQuery extends CallbackQuery
{
    protected string $type = 'MANAGEINVOICES';
    protected int $perm = Roles::ADMIN->value;

    public function start(int $page = 1, int $currentPage = 0, string $sortBy = 'id', string $sortDir = 'desc'): void
    {
        ManageInvoicesFeature::menu($page, $currentPage, $sortBy, $sortDir)->update();
    }

    /**
     * @throws TelegramSDKException
     */
    function show(Invoice $invoice, int $lastPage = 1): void
    {
        ManageInvoicesFeature::show($invoice, $lastPage)->update();
    }

    /**
     * @throws TelegramSDKException
     */
    function markAsPaid(Invoice $invoice, int $lastPage = 1): void
    {
        if($invoice->status == 'paid') {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => __('tbe-billing::manage_invoices.alerts.already_paid'),
                'show_alert' => true,
            ]);
            return;
        }
        $invoice->markAsPaid();
        ManageInvoicesFeature::show($invoice, $lastPage)->update();
    }

    /**
     * @throws TelegramSDKException
     */
    function markAsPending(Invoice $invoice, int $lastPage = 1): void
    {
        if($invoice->status == 'pending') {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => __('tbe-billing::manage_invoices.alerts.already_pending'),
                'show_alert' => true,
            ]);
            return;
        }
        $invoice->markAsPending();
        ManageInvoicesFeature::show($invoice, $lastPage)->update();
    }

    /**
     * @throws TelegramSDKException
     */
    function markAsFailed(Invoice $invoice, int $lastPage = 1): void
    {
        if($invoice->status == 'failed') {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => __('tbe-billing::manage_invoices.alerts.already_failed'),
                'show_alert' => true,
            ]);
            return;
        }
        $invoice->markAsFailed();
        ManageInvoicesFeature::show($invoice, $lastPage)->update();
    }
}
