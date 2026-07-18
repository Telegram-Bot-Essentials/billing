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
    public static function menu(int $page = 1, int $currentPage = 0, string $sortBy = 'id', string $sortDir = 'desc'): TelegramResponse
    {
        $allowedSortColumns = ['id', 'payable_type', 'status', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSortColumns) ? $sortBy : 'id';
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        $text = __('tbe-billing::manage_invoices.main.text.list');

        $replyMarkup = Keyboard::make()
            ->inline();

        $invoices = Invoice::query()->orderBy($sortBy, $sortDir)->paginate(perPage: 10, page: $page);

        TelegramPaginator::validatePageNumber($page, $currentPage, $invoices);

        if (count($invoices) == 0) {
            $text = __('tbe-billing::manage_invoices.main.text.empty');
            return new TelegramResponse(
                text: $text,
                parseMode: 'HTML'
            );
        }

        $sortIndicator = fn(string $col) => $sortBy === $col ? ($sortDir === 'desc' ? ' ↓' : ' ↑') : '';
        $nextDir = fn(string $col) => ($sortBy === $col && $sortDir === 'desc') ? 'asc' : 'desc';

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-billing::manage_invoices.main.keys.col_id') . $sortIndicator('id'),
                'callback_data' => encodeCallback(self::$type, 'start', [$page, 0, 'id', $nextDir('id')])
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe-billing::manage_invoices.main.keys.col_type') . $sortIndicator('payable_type'),
                'callback_data' => encodeCallback(self::$type, 'start', [$page, 0, 'payable_type', $nextDir('payable_type')])
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe-billing::manage_invoices.main.keys.col_time') . $sortIndicator('created_at'),
                'callback_data' => encodeCallback(self::$type, 'start', [$page, 0, 'created_at', $nextDir('created_at')])
            ]),
        ]);

        foreach ($invoices as $invoice) {
            $telegramUser = $invoice->botUser->telegramUser;
            $userLabel = $telegramUser->username ? "@{$telegramUser->username}" : ($telegramUser->first_name ?: $telegramUser->full_name);
            $typeAbbrev = substr(str_replace('Order', '', getResourceName($invoice->payable_type)), 0, 3);

            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => "{$typeAbbrev} {$userLabel}",
                    'callback_data' => encodeCallback(self::$type, 'show', [$invoice->id, $page])
                ]),
                Keyboard::inlineButton(array_filter([
                    'text' => currency()->currencyFormat($invoice->price, currencyCode: $invoice->currency, thousandSeparator: ','),
                    'style' => match ($invoice->status) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => null
                    },
                    'callback_data' => encodeCallback(self::$type, 'show', [$invoice->id, $page])
                ])),
                Keyboard::inlineButton([
                    'text' => $invoice->created_at->format('m-d H:i'),
                    'callback_data' => encodeCallback(self::$type, 'show', [$invoice->id, $page])
                ]),
            ]);
        }

        $replyMarkup->row(TelegramPaginator::makeNavigationButtonsRow(self::$type, $page, $invoices->lastPage(), extraParams: [$sortBy, $sortDir]));

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

        $replyMarkup->row([
            Keyboard::inlineButton(array_filter([
                'text' => __('tbe-billing::manage_invoices.main.keys.status_paid'),
                'style' => ($invoice->status == 'paid') ? 'success' : null,
                'callback_data' => encodeCallback(self::$type, 'mark_as_paid', [$invoice->id, $lastPage])
            ]))
        ]);
        $replyMarkup->row([
            Keyboard::inlineButton(array_filter([
                'text' => __('tbe-billing::manage_invoices.main.keys.status_pending'),
                'style' => ($invoice->status == 'pending') ? 'success' : null,
                'callback_data' => encodeCallback(self::$type, 'mark_as_pending', [$invoice->id, $lastPage])
            ]))
        ]);
        $replyMarkup->row([
            Keyboard::inlineButton(array_filter([
                'text' => __('tbe-billing::manage_invoices.main.keys.status_failed'),
                'style' => ($invoice->status == 'failed') ? 'success' : null,
                'callback_data' => encodeCallback(self::$type, 'mark_as_failed', [$invoice->id, $lastPage])
            ]))
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-billing::manage_invoices.main.keys.back_to_list'),
                'callback_data' => encodeCallback(self::$type, 'start', [$lastPage, 0, 'id', 'desc'])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }
}
