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

//    public function handle(array $params): void
//    {
//        $this->params = $params;
//        switch (strtolower($params[0])) {
//            case "start":
//                // Use dependsOn() to give condition to check if the callback is allowed
//                // dependsOn(false);
//                $this->start();
//                break;
//            case "show":
//                $this->show();
//                break;
//            case "mark_as_pending":
//                $this->markAsPending();
//                break;
//            case "mark_as_paid":
//                $this->markAsPaid();
//                break;
//            case "mark_as_failed":
//                $this->markAsFailed();
//                break;
//        }
//    }

    public function start(): void
    {
        $page = intval($this->params[1] ?? 1);
        $currentPage = intval($this->params[2] ?? 0);
        ManageInvoicesFeature::menu($page, $currentPage)->update();
    }

    /**
     * @throws TelegramSDKException
     */
    private function show(): void
    {
        $invoice = Invoice::findOrFail($this->params[1]);
        $lastPage = intval($this->params[2] ?? 1);
        ManageInvoicesFeature::show($invoice, $lastPage)->update();
    }

    /**
     * @throws TelegramSDKException
     */
    private function markAsPaid(): void
    {
        $invoice = Invoice::findOrFail($this->params[1]);
        if($invoice->status == 'paid') {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => "Invoice is already paid", // TODO: Localize this message
                'show_alert' => true,
            ]);
            return;
        }
        $invoice->markAsPaid();
        $lastPage = intval($this->params[2] ?? 1);
        ManageInvoicesFeature::show($invoice, $lastPage)->update();
    }

    /**
     * @throws TelegramSDKException
     */
    private function markAsPending(): void
    {
        $invoice = Invoice::findOrFail($this->params[1]);
        if($invoice->status == 'pending') {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => "Invoice is already pending", // TODO: Localize this message
                'show_alert' => true,
            ]);
            return;
        }
        $invoice->markAsPending();
        $lastPage = intval($this->params[2] ?? 1);
        ManageInvoicesFeature::show($invoice, $lastPage)->update();
    }

    /**
     * @throws TelegramSDKException
     */
    private function markAsFailed(): void
    {
        $invoice = Invoice::findOrFail($this->params[1]);
        if($invoice->status == 'failed') {
            wHook()->api()->answerCallbackQuery([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => "Invoice is already failed", // TODO: Localize this message
                'show_alert' => true,
            ]);
            return;
        }
        $invoice->markAsFailed();
        $lastPage = intval($this->params[2] ?? 1);
        ManageInvoicesFeature::show($invoice, $lastPage)->update();
    }
}
