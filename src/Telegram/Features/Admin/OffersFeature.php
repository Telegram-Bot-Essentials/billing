<?php

namespace TelegramBotEssentials\Billing\Telegram\Features\Admin;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Billing\Models\Offer;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
use TelegramBotEssentials\Essence\Services\TelegramPaginator;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

class OffersFeature
{
    public static string $type = 'OFFERS';

    /** Fields editable after creation, in display order. */
    public const EDITABLE_FIELDS = ['amount', 'max_discount', 'min_price', 'max_price', 'usage_limit', 'usage_limit_per_user', 'expires_at'];

    /**
     * @throws InvalidPageNumber
     */
    public static function menu(int $page = 1, int $currentPage = 0): TelegramResponse
    {
        $offers = Offer::query()->where('bot_id', wHook()->bot()->id)->orderByDesc('id')->paginate(perPage: 10, page: $page);

        TelegramPaginator::validatePageNumber($page, $currentPage, $offers);

        $replyMarkup = Keyboard::make()->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-billing::offers.main.keys.create'),
                'callback_data' => encodeCallback(self::$type, 'create', [$page]),
            ]),
        ]);

        if (count($offers) == 0) {
            return new TelegramResponse(
                text: __('tbe-billing::offers.main.text.empty'),
                replyMarkup: $replyMarkup,
                parseMode: 'HTML'
            );
        }

        foreach ($offers as $offer) {
            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => self::listLabel($offer),
                    'callback_data' => encodeCallback(self::$type, 'show', [$offer->id, $page]),
                ]),
            ]);
        }

        $replyMarkup->row(TelegramPaginator::makeNavigationButtonsRow(self::$type, $page, $offers->lastPage()));

        return new TelegramResponse(
            text: __('tbe-billing::offers.main.text.list'),
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    private static function listLabel(Offer $offer): string
    {
        $badge = $offer->is_enabled ? '✅' : '🚫';
        $value = self::amountLabel($offer);

        return "{$badge} {$offer->code} · {$value}";
    }

    /**
     * The discount as an admin reads it. The amount column is a 30-digit
     * decimal, so a percentage comes back as `100.000000000000000000000000000000`
     * and its trailing zeros have to go; a fixed amount is formatted as money,
     * which already drops empty decimals.
     */
    private static function amountLabel(Offer $offer): string
    {
        if ($offer->type !== 'percentage') {
            return currency()->priceFormat($offer->amount);
        }

        $amount = str_contains($offer->amount, '.') ? rtrim(rtrim($offer->amount, '0'), '.') : $offer->amount;

        return $amount.'%';
    }

    public static function show(Offer $offer, int $lastPage = 1): TelegramResponse
    {
        $text = __('tbe-billing::offers.main.text.show', [
            'code' => $offer->code,
            'type' => __('tbe-billing::offers.main.type.'.$offer->type),
            'amount' => self::amountLabel($offer),
            'maxDiscount' => $offer->max_discount !== null ? currency()->priceFormat($offer->max_discount) : __('tbe-billing::offers.main.unlimited'),
            'minPrice' => $offer->min_price !== null ? currency()->priceFormat($offer->min_price) : __('tbe-billing::offers.main.unlimited'),
            'maxPrice' => $offer->max_price !== null ? currency()->priceFormat($offer->max_price) : __('tbe-billing::offers.main.unlimited'),
            'usageLimit' => $offer->usage_limit ?? __('tbe-billing::offers.main.unlimited'),
            'usageLimitPerUser' => $offer->usage_limit_per_user ?? __('tbe-billing::offers.main.unlimited'),
            'expiresAt' => $offer->expires_at?->format('Y-m-d') ?? __('tbe-billing::offers.main.never'),
            'status' => $offer->is_enabled ? __('tbe-billing::offers.main.enabled') : __('tbe-billing::offers.main.disabled'),
            'redeemed' => $offer->redemptions()->count(),
        ]);

        $replyMarkup = Keyboard::make()->inline();

        $replyMarkup->row([
            Keyboard::inlineButton(array_filter([
                'text' => $offer->is_enabled ? __('tbe-billing::offers.main.keys.disable') : __('tbe-billing::offers.main.keys.enable'),
                'style' => $offer->is_enabled ? 'success' : null,
                'callback_data' => encodeCallback(self::$type, 'toggle', [$offer->id, $lastPage]),
            ])),
            Keyboard::inlineButton([
                'text' => __('tbe-billing::offers.main.keys.stats'),
                'callback_data' => encodeCallback(self::$type, 'stats', [$offer->id, $lastPage]),
            ]),
        ]);

        foreach (self::editableFieldsFor($offer) as $field) {
            $row = [
                Keyboard::inlineButton([
                    'text' => __('tbe-billing::offers.wizard.fields.'.$field.'.editLabel'),
                    'callback_data' => encodeCallback(self::$type, 'edit', [$offer->id, $field, $lastPage]),
                ]),
            ];

            if ($field !== 'amount' && $offer->{$field} !== null) {
                $row[] = Keyboard::inlineButton([
                    'text' => __('tbe-billing::offers.main.keys.clear'),
                    'callback_data' => encodeCallback(self::$type, 'clearField', [$offer->id, $field, $lastPage]),
                ]);
            }

            $replyMarkup->row($row);
        }

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-billing::offers.main.keys.delete'),
                'callback_data' => encodeCallback(self::$type, 'delete', [$offer->id, $lastPage]),
            ]),
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-billing::offers.main.keys.back_to_list'),
                'callback_data' => encodeCallback(self::$type, 'start', [$lastPage]),
            ]),
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function stats(Offer $offer, int $lastPage = 1): TelegramResponse
    {
        $redemptions = $offer->redemptions()->with('botUser.telegramUser')->latest()->limit(10)->get();
        $totalDiscount = $offer->redemptions()->sum('amount_applied');

        $lines = $redemptions->map(function ($redemption) {
            $user = $redemption->botUser?->telegramUser;
            $label = $user?->username ? "@{$user->username}" : ($user?->full_name ?? '—');

            return "· {$label} — ".currency()->priceFormat($redemption->amount_applied)." ({$redemption->created_at->format('Y-m-d')})";
        })->implode("\r\n");

        $text = __('tbe-billing::offers.main.text.stats', [
            'code' => $offer->code,
            'count' => $offer->redemptions()->count(),
            'total' => currency()->priceFormat($totalDiscount),
            'recent' => $lines !== '' ? $lines : __('tbe-billing::offers.main.text.noRedemptions'),
        ]);

        $replyMarkup = Keyboard::make()->inline()->row([
            Keyboard::inlineButton([
                'text' => __('tbe-billing::offers.main.keys.back_to_offer'),
                'callback_data' => encodeCallback(self::$type, 'show', [$offer->id, $lastPage]),
            ]),
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    private static function editableFieldsFor(Offer $offer): array
    {
        return $offer->type === 'percentage'
            ? self::EDITABLE_FIELDS
            : array_values(array_diff(self::EDITABLE_FIELDS, ['max_discount']));
    }
}
