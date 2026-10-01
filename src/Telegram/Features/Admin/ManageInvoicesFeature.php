<?php

namespace TelegramBotEssentials\Billing\Telegram\Features\Admin;

use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Services\InvoiceStats;
use TelegramBotEssentials\Billing\Telegram\Features\Member\InvoiceFeature;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
use TelegramBotEssentials\Essence\Services\TelegramPaginator;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

class ManageInvoicesFeature
{
    public static string $type = 'MANAGEINVOICES';

    // TODO: Implement static functions for generating bot messages

    /**
     * @param  int  $userId  narrows the list to one member's invoices (their user-management profile links here); 0 lists every invoice
     *
     * @throws InvalidPageNumber
     */
    public static function menu(int $page = 1, int $currentPage = 0, string $sortBy = 'id', string $sortDir = 'desc', int $userId = 0): TelegramResponse
    {
        $allowedSortColumns = ['id', 'bot_user_id', 'payable_type', 'status', 'created_at', 'price'];
        $sortBy = in_array($sortBy, $allowedSortColumns) ? $sortBy : 'id';
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        $text = __('tbe-billing::manage_invoices.main.text.list');

        // The whole shop's figures, so a single member's list leaves them out.
        if (! $userId && $stats = app(InvoiceStats::class)->render()) {
            $text .= "\r\n\r\n".$stats;
        }

        $replyMarkup = Keyboard::make()
            ->inline();

        $invoices = Invoice::query()->when($userId, fn ($query) => $query->where('bot_user_id', $userId))->orderBy($sortBy, $sortDir)->paginate(perPage: 10, page: $page);

        TelegramPaginator::validatePageNumber($page, $currentPage, $invoices);

        if (count($invoices) == 0) {
            $text = __('tbe-billing::manage_invoices.main.text.empty');

            return new TelegramResponse(
                text: $text,
                replyMarkup: $userId ? Keyboard::make()->inline()->row([self::backToProfile($userId)]) : null,
                parseMode: 'HTML'
            );
        }

        $sortIndicator = fn (string $col) => $sortBy === $col ? ($sortDir === 'desc' ? ' ↓' : ' ↑') : '';
        $nextDir = fn (string $col) => ($sortBy === $col && $sortDir === 'desc') ? 'asc' : 'desc';

        $typeDateCycle = [
            ['payable_type', 'desc'],
            ['payable_type', 'asc'],
            ['created_at', 'desc'],
            ['created_at', 'asc'],
        ];
        $typeDateIndex = collect($typeDateCycle)->search(fn ($step) => $step[0] === $sortBy && $step[1] === $sortDir);
        [$nextTypeDateSortBy, $nextTypeDateSortDir] = $typeDateCycle[$typeDateIndex === false ? 0 : ($typeDateIndex + 1) % 4];
        $typeDateIndicator = in_array($sortBy, ['payable_type', 'created_at']) ? ($sortDir === 'desc' ? ' ↓' : ' ↑') : '';

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-billing::manage_invoices.main.keys.col_id').$sortIndicator('bot_user_id'),
                'callback_data' => encodeCallback(self::$type, 'start', [$page, 0, 'bot_user_id', $nextDir('bot_user_id'), $userId]),
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe-billing::manage_invoices.main.keys.col_type').$typeDateIndicator,
                'callback_data' => encodeCallback(self::$type, 'start', [$page, 0, $nextTypeDateSortBy, $nextTypeDateSortDir, $userId]),
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe-billing::manage_invoices.main.keys.col_status').$sortIndicator('price'),
                'callback_data' => encodeCallback(self::$type, 'start', [$page, 0, 'price', $nextDir('price'), $userId]),
            ]),
        ]);

        foreach ($invoices as $invoice) {
            $telegramUser = $invoice->botUser->telegramUser;
            $userLabel = $telegramUser->username ? "@{$telegramUser->username}" : ($telegramUser->first_name ?: $telegramUser->full_name);
            $typeAbbrev = substr(str_replace('Order', '', getResourceName($invoice->payable_type)), 0, 3);

            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => $userLabel,
                    'callback_data' => encodeCallback(self::$type, 'show', [$invoice->id, $page, $sortBy, $sortDir, $userId]),
                ]),
                Keyboard::inlineButton([
                    'text' => $invoice->created_at->format('y-m-d')." {$typeAbbrev}",
                    'callback_data' => encodeCallback(self::$type, 'show', [$invoice->id, $page, $sortBy, $sortDir, $userId]),
                ]),
                Keyboard::inlineButton(array_filter([
                    'text' => currency()->priceFormat($invoice->price, currency: $invoice->currency),
                    'style' => match ($invoice->status) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => null
                    },
                    'callback_data' => encodeCallback(self::$type, 'show', [$invoice->id, $page, $sortBy, $sortDir, $userId]),
                ])),
            ]);
        }

        TelegramPaginator::addNavigationRow($replyMarkup, self::$type, $page, $invoices->lastPage(), extraParams: [$sortBy, $sortDir, $userId]);

        if ($userId) {
            $replyMarkup->row([self::backToProfile($userId)]);
        }

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

    public static function show(Invoice $invoice, int $lastPage = 1, string $sortBy = 'id', string $sortDir = 'desc', int $userId = 0): TelegramResponse
    {
        $statusIndicator = self::statusIndicator($invoice->status);
        $attemptStatus = $invoice->paymentAttempt
            ? self::statusIndicator($invoice->paymentAttempt->status)
            : __('tbe-billing::manage_invoices.main.keys.no_attempt');

        try {
            $orderDescription = $invoice->payable?->description ?? '—';
        } catch (\Throwable) {
            $orderDescription = '—';
        }

        $text = __('tbe-billing::manage_invoices.main.text.show', [
            'invoiceId' => $invoice->id,
            'invoiceOwner' => "<a href=\"tg://user?id={$invoice->botUser->telegramUser->peer_id}\">{$invoice->botUser->telegramUser->full_name}</a>",
            'invoiceAmount' => currency()->priceFormat($invoice->price),
            'invoiceStatus' => $statusIndicator,
            'orderType' => getResourceName($invoice->payable_type),
            'paymentAttempt' => $invoice->paymentAttempt?->id ?? '—',
            'paymentAttemptStatus' => $attemptStatus,
            'paymentAttemptDate' => $invoice->paymentAttempt?->created_at ?? '—',
            'orderDescription' => $orderDescription,
        ]);

        if ($offerSummary = InvoiceFeature::offerSummary($invoice)) {
            $text .= "\r\n".$offerSummary;
        }

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([
            Keyboard::inlineButton(array_filter([
                'text' => __('tbe-billing::manage_invoices.main.keys.status_paid'),
                'style' => ($invoice->status == 'paid') ? 'success' : null,
                'callback_data' => encodeCallback(self::$type, 'mark_as_paid', [$invoice->id, $lastPage, $sortBy, $sortDir, $userId]),
            ])),
        ]);
        $replyMarkup->row([
            Keyboard::inlineButton(array_filter([
                'text' => __('tbe-billing::manage_invoices.main.keys.status_pending'),
                'style' => ($invoice->status == 'pending') ? 'success' : null,
                'callback_data' => encodeCallback(self::$type, 'mark_as_pending', [$invoice->id, $lastPage, $sortBy, $sortDir, $userId]),
            ])),
        ]);
        $replyMarkup->row([
            Keyboard::inlineButton(array_filter([
                'text' => __('tbe-billing::manage_invoices.main.keys.status_failed'),
                'style' => ($invoice->status == 'failed') ? 'success' : null,
                'callback_data' => encodeCallback(self::$type, 'mark_as_failed', [$invoice->id, $lastPage, $sortBy, $sortDir, $userId]),
            ])),
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-billing::manage_invoices.main.keys.back_to_list'),
                'callback_data' => encodeCallback(self::$type, 'start', [$lastPage, 0, $sortBy, $sortDir, $userId]),
            ]),
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    /** The way back to the member's profile in user management. */
    private static function backToProfile(int $userId): Button
    {
        return Keyboard::inlineButton([
            'text' => __('tbe-billing::manage_invoices.main.keys.back_to_profile'),
            'callback_data' => encodeCallback('BOTUSERS', 'show', [$userId]),
        ]);
    }
}
