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
        $allowedSortColumns = ['id', 'user', 'payable_type', 'status', 'created_at', 'price'];
        $sortBy = in_array($sortBy, $allowedSortColumns) ? $sortBy : 'id';
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        $text = __('tbe-billing::manage_invoices.main.text.list');

        $replyMarkup = Keyboard::make()
            ->inline();

        $invoicesQuery = Invoice::query();
        if ($sortBy === 'user') {
            $invoicesQuery->select('invoices.*')
                ->join('bot_users', 'invoices.bot_user_id', '=', 'bot_users.id')
                ->join('telegram_users', 'bot_users.telegram_user_peer_id', '=', 'telegram_users.peer_id')
                ->orderByRaw("COALESCE(telegram_users.username, telegram_users.first_name) {$sortDir}");
        } else {
            $invoicesQuery->orderBy($sortBy, $sortDir);
        }
        $invoices = $invoicesQuery->paginate(perPage: 10, page: $page);

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
                'text' => __('tbe-billing::manage_invoices.main.keys.col_id') . $sortIndicator('user'),
                'callback_data' => encodeCallback(self::$type, 'start', [$page, 0, 'user', $nextDir('user')])
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe-billing::manage_invoices.main.keys.col_type') . $sortIndicator('payable_type'),
                'callback_data' => encodeCallback(self::$type, 'start', [$page, 0, 'payable_type', $nextDir('payable_type')])
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe-billing::manage_invoices.main.keys.col_status') . $sortIndicator('price'),
                'callback_data' => encodeCallback(self::$type, 'start', [$page, 0, 'price', $nextDir('price')])
            ]),
        ]);

        foreach ($invoices as $invoice) {
            $telegramUser = $invoice->botUser->telegramUser;
            $userLabel = $telegramUser->username ? "@{$telegramUser->username}" : ($telegramUser->first_name ?: $telegramUser->full_name);
            $typeAbbrev = substr(str_replace('Order', '', getResourceName($invoice->payable_type)), 0, 3);

            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => $userLabel,
                    'callback_data' => encodeCallback(self::$type, 'show', [$invoice->id, $page])
                ]),
                Keyboard::inlineButton([
                    'text' => $invoice->created_at->format('y-m-d') . " {$typeAbbrev}",
                    'callback_data' => encodeCallback(self::$type, 'show', [$invoice->id, $page])
                ]),
                Keyboard::inlineButton(array_filter([
                    'text' => currency()->priceFormat($invoice->price, currency: $invoice->currency),
                    'style' => match ($invoice->status) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => null
                    },
                    'callback_data' => encodeCallback(self::$type, 'show', [$invoice->id, $page])
                ])),
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
