<?php

namespace TelegramBotEssentials\Billing\Telegram\Features\Admin;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Services\TelegramPaginator;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

class ManageInvoicesFeature
{
    static string $type = 'MANAGEINVOICES';

    // TODO: Implement static functions for generating bot messages

    /**
     * @throws InvalidPageNumber
     */
    public static function menu(int $page = 1, int $currentPage = 0): TelegramResponse
    {
        $text = 'invoices';

        $replyMarkup = Keyboard::make()
            ->inline();

        $invoices = Invoice::paginate(perPage: 10, page: $page);

        TelegramPaginator::validatePageNumber($page, $currentPage, $invoices);

        if (count($invoices) == 0) {
            $text = 'No invoices found';
            return new TelegramResponse(
                text: $text,
                parseMode: 'HTML'
            );
        }

        foreach ($invoices as $invoice) {
            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => __('tbe-billing::manage_invoices.main.keys.invoice', [
                        'invoiceId' => $invoice->id,
                        'resourceName' => getResourceName($invoice->payable_type),
                        'price' => currency()->priceFormat($invoice->price, currency: $invoice->currency),
                        'userFullName' => $invoice->botUser->telegramUser->full_name,
                        'status' => $invoice->status == 'paid' ?
                            __('tbe-billing::general.status.enabledEmoji') :
                            ($invoice->status == 'failed' ? __('tbe-billing::general.status.xEmoji')
                                : __('tbe-billing::general.status.pendingEmoji')),
                    ]),
                    'callback_data' => encodeCallback(self::$type, ['show', $invoice->id, $page])
                ])
            ]);
        }

        $replyMarkup->row(TelegramPaginator::makeNavigationButtonsRow(self::$type, $page, $invoices->lastPage()));

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function show(Invoice $invoice, int $lastPage = 1): TelegramResponse
    {
        $text = __('tbe-billing::manage_invoices.main.text.show', [
            'invoiceId' => $invoice->id,
            'invoiceOwner' => "<a href=\"tg://user?id={$invoice->botUser->telegramUser->peer_id}\">{$invoice->botUser->telegramUser->full_name}</a>",
            'invoiceAmount' => currency()->priceFormat($invoice->price),
            'invoiceStatus' => $invoice->status,
            'paymentAttempt' => $invoice->paymentAttempt?->id,
            'paymentAttemptStatus' => $invoice->status,
            'paymentAttemptDate' => $invoice->paymentAttempt?->created_at,
            'orderDescription' => $invoice->paymentAttempt?->description,
        ]);

        $replyMarkup = Keyboard::make()
            ->inline();

        // TODO : Localize this buttons
        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => 'failed' . ($invoice->status == 'failed' ? ' ✅' : ''),
                'callback_data' => encodeCallback(self::$type, ['mark_as_failed', $invoice->id, $lastPage])
            ]),
            Keyboard::inlineButton([
                'text' => 'pending' . ($invoice->status == 'pending' ? ' ✅' : ''),
                'callback_data' => encodeCallback(self::$type, ['mark_as_pending', $invoice->id, $lastPage])
            ]),
            Keyboard::inlineButton([
                'text' => 'paid' . ($invoice->status == 'paid' ? ' ✅' : ''),
                'callback_data' => encodeCallback(self::$type, ['mark_as_paid', $invoice->id, $lastPage])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-billing::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, ['start', $lastPage, 0])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }
}
