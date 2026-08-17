<?php

namespace TelegramBotEssentials\Billing\Services;

use Illuminate\Support\Collection;
use TelegramBotEssentials\Billing\Models\Abstract\Order;
use TelegramBotEssentials\Billing\Models\Invoice;

/**
 * What has been paid for, broken down by what was bought.
 *
 * Kept per payable type rather than rolled into one revenue figure: a wallet
 * top-up and the purchase it later pays for are both paid invoices, so a single
 * total would count the same money twice with no way to tell. Split by type,
 * the double entry is plain to read instead of hidden.
 */
class InvoiceStats
{
    /**
     * Every paid invoice grouped by what it was for, in one pass.
     *
     * Windows are measured on updated_at, which is when markAsPaid() last wrote
     * the row; invoices carry no paid_at column of their own.
     */
    public function paidByPayableType(): Collection
    {
        $day = now()->subDay();
        $week = now()->subWeek();
        $month = now()->subMonth();

        return Invoice::query()
            ->where('status', 'paid')
            ->groupBy('payable_type')
            ->selectRaw('payable_type')
            ->selectRaw('SUM(price) as total_sum')
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(CASE WHEN updated_at > ? THEN price ELSE 0 END) as day_sum', [$day])
            ->selectRaw('SUM(CASE WHEN updated_at > ? THEN price ELSE 0 END) as week_sum', [$week])
            ->selectRaw('SUM(CASE WHEN updated_at > ? THEN price ELSE 0 END) as month_sum', [$month])
            ->selectRaw('SUM(CASE WHEN updated_at > ? THEN 1 ELSE 0 END) as month_count', [$month])
            ->selectRaw('COUNT(DISTINCT CASE WHEN updated_at > ? THEN bot_user_id END) as month_buyers', [$month])
            ->havingRaw('month_count > 0')
            ->orderByDesc('month_sum')
            ->toBase()
            ->get();
    }

    /**
     * The block printed in the bot user list header, or null when nothing has
     * been paid for in the last month.
     */
    public function render(): ?string
    {
        $rows = $this->paidByPayableType();

        if ($rows->isEmpty()) {
            return null;
        }

        return $rows
            ->map(fn (object $row) => __('tbe-billing::stats.payable', [
                'label' => self::labelFor($row->payable_type),
                // Named once per block: six amounts each carrying their own
                // "تومان" drowned out the figures they were labelling.
                'currency' => currency()->getCurrentCurrencySymbol(),
                'day' => self::amount($row->day_sum),
                'week' => self::amount($row->week_sum),
                'month' => self::amount($row->month_sum),
                'monthCount' => number_format($row->month_count),
                'total' => self::amount($row->total_sum),
                'totalCount' => number_format($row->total_count),
                'buyers' => number_format($row->month_buyers),
            ]))
            ->implode("\r\n\r\n");
    }

    private static function amount(string $amount): string
    {
        return currency()->currencyFormat($amount, thousandSeparator: ',');
    }

    /**
     * payable_type holds a class name, so an order whose package has since been
     * removed still has to read as something.
     */
    public static function labelFor(string $payableType): string
    {
        return is_subclass_of($payableType, Order::class)
            ? $payableType::statsLabel()
            : class_basename($payableType);
    }
}
