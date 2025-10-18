<?php

namespace TelegramBotEssentials\Billing\Telegram\Features\Admin;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
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
        $text = __('tbe-billing::manage_invoices.main.text.list');

        $replyMarkup = Keyboard::make()
            ->inline();

        $invoices = Invoice::query()->orderByDesc('id')->paginate(perPage: 10, page: $page);

        TelegramPaginator::validatePageNumber($page, $currentPage, $invoices);

        if (count($invoices) == 0) {
            $text = __('tbe-billing::manage_invoices.main.text.empty');
            return new TelegramResponse(
                text: $text,
                parseMode: 'HTML'
            );
        }

        foreach ($invoices as $invoice) {
            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => "#{$invoice->id} - ". $invoice->botUser->telegramUser->full_name,
                    'callback_data' => encodeCallback(self::$type, 'show', [$invoice->id, $page])
                ]),
                Keyboard::inlineButton([
                    'text' => getResourceName($invoice->payable_type),
                    'callback_data' => encodeCallback(self::$type, 'show', [$invoice->id, $page])
                ]),

                Keyboard::inlineButton([
                    'text' => currency()->priceFormat($invoice->price, currency: $invoice->currency) . ' ' . self::statusIndicatorEmoji($invoice->status),
                    'callback_data' => encodeCallback(self::$type, 'show', [$invoice->id, $page])
                ]),
            ]);
        }

        $replyMarkup->row(TelegramPaginator::makeNavigationButtonsRow(self::$type, $page, $invoices->lastPage()));

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    private static function statusIndicator(?string $status): string
    {
        return match ($status) {
            'paid' => __('tbe-billing::manage_invoices.main.keys.status_indicator.paid'),
            'failed' => __('tbe-billing::manage_invoices.main.keys.status_indicator.failed'),
            default => __('tbe-billing::manage_invoices.main.keys.status_indicator.pending'),
        };
    }

    private static function statusIndicatorEmoji(?string $status): string
    {
        return match ($status) {
            'paid' => __('tbe::general.status.enabledEmoji'),
            'failed' => __('tbe::general.status.xEmoji'),
            default => __('tbe::general.status.pendingEmoji'),
        };
    }

    public static function show(Invoice $invoice, int $lastPage = 1): TelegramResponse
    {
        $statusIndicator = self::statusIndicator($invoice->status);
        $attemptStatus = self::statusIndicator($invoice->paymentAttempt?->status);

        $text = __('tbe-billing::manage_invoices.main.text.show', [
            'invoiceId' => $invoice->id,
            'invoiceOwner' => "<a href=\"tg://user?id={$invoice->botUser->telegramUser->peer_id}\">{$invoice->botUser->telegramUser->full_name}</a>",
            'invoiceAmount' => currency()->priceFormat($invoice->price),
            'invoiceStatus' => $statusIndicator,
            'paymentAttempt' => $invoice->paymentAttempt?->id,
            'paymentAttemptStatus' => $attemptStatus,
            'paymentAttemptDate' => $invoice->paymentAttempt?->created_at,
            'orderDescription' => $invoice->paymentAttempt?->description,
        ]);

        $replyMarkup = Keyboard::make()
            ->inline();

        $selectedSuffix = __('tbe-billing::manage_invoices.main.keys.status_selected_suffix');


        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-billing::manage_invoices.main.keys.status_paid')
                    . ($invoice->status == 'paid' ? $selectedSuffix : ''),
                'callback_data' => encodeCallback(self::$type, 'mark_as_paid', [$invoice->id, $lastPage])
            ])
        ]);
        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-billing::manage_invoices.main.keys.status_pending')
                    . ($invoice->status == 'pending' ? $selectedSuffix : ''),
                'callback_data' => encodeCallback(self::$type, 'mark_as_pending', [$invoice->id, $lastPage])
            ])
        ]);
        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-billing::manage_invoices.main.keys.status_failed')
                    . ($invoice->status == 'failed' ? $selectedSuffix : ''),
                'callback_data' => encodeCallback(self::$type, 'mark_as_failed', [$invoice->id, $lastPage])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-billing::manage_invoices.main.keys.back_to_list'),
                'callback_data' => encodeCallback(self::$type, 'start', [$lastPage, 0])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }
}
