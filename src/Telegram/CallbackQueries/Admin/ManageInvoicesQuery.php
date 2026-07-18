<?php

namespace TelegramBotEssentials\Billing\Telegram\CallbackQueries\Admin;

use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Billing\Telegram\Features\Admin\ManageInvoicesFeature;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Models\MessageMeta;
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
    function show(Invoice $invoice, int $lastPage = 1, string $sortBy = 'id', string $sortDir = 'desc'): void
    {
        ManageInvoicesFeature::show($invoice, $lastPage, $sortBy, $sortDir)->update();
    }

    /**
     * @throws TelegramSDKException
     */
    function markAsPaid(Invoice $invoice, int $lastPage = 1, string $sortBy = 'id', string $sortDir = 'desc'): void
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
        ManageInvoicesFeature::show($invoice, $lastPage, $sortBy, $sortDir)->update();
    }

    /**
     * @throws TelegramSDKException
     */
    function markAsPending(Invoice $invoice, int $lastPage = 1, string $sortBy = 'id', string $sortDir = 'desc'): void
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
        ManageInvoicesFeature::show($invoice, $lastPage, $sortBy, $sortDir)->update();
    }

    /**
     * @throws TelegramSDKException
     */
    function markAsFailed(Invoice $invoice, int $lastPage = 1, string $sortBy = 'id', string $sortDir = 'desc'): void
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
        ManageInvoicesFeature::show($invoice, $lastPage, $sortBy, $sortDir)->update();
    }

    /**
     * @throws LogicException
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     */
    public function setStartPage(string $sortBy = 'id', string $sortDir = 'desc'): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->lockAction(__('tbe-billing::manage_invoices.main.text.waiting_page'));
        wHook()->user()->changeState(encodeAnswerState($this->type, 'setStartPage', [
            'message_meta_id' => $messageMeta->id,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
        ]));
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => __('tbe-billing::manage_invoices.main.text.enter_page'),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }
}
