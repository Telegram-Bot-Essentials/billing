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
        $offers = Offer::query()->where('bot_id', wHook()->bot()->id)->whereNotNull('type')->orderByDesc('id')->paginate(perPage: 10, page: $page);

        TelegramPaginator::validatePageNumber($page, $currentPage, $offers);

        if (count($offers) == 0) {
            return new TelegramResponse(
                text: __('tbe-billing::offers.main.text.empty'),
                replyMarkup: Keyboard::make()->inline()->row([
                    Keyboard::inlineButton([
                        'text' => __('tbe-billing::offers.main.keys.create'),
                        'callback_data' => encodeCallback(self::$type, 'create', [$page]),
                    ]),
                ]),
                parseMode: 'HTML'
            );
        }

        $replyMarkup = Keyboard::make()->inline();

        foreach ($offers as $offer) {
            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => self::listLabel($offer),
                    'callback_data' => encodeCallback(self::$type, 'show', [$offer->id, $page]),
                ]),
            ]);
        }

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-billing::offers.main.keys.create'),
                'callback_data' => encodeCallback(self::$type, 'create', [$page]),
            ]),
        ]);

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
        $value = $offer->type === 'percentage'
            ? "{$offer->amount}%"
            : currency()->priceFormat($offer->amount);

        return "{$badge} {$offer->code} · {$value}";
    }

    public static function show(Offer $offer, int $lastPage = 1): TelegramResponse
    {
        $text = __('tbe-billing::offers.main.text.show', [
            'code' => $offer->code,
            'type' => __('tbe-billing::offers.main.type.'.$offer->type),
            'amount' => $offer->type === 'percentage' ? "{$offer->amount}%" : currency()->priceFormat($offer->amount),
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

    public static function choosingType(Offer $offer, int $lastPage, int $messageMetaId): TelegramResponse
    {
        $replyMarkup = Keyboard::make()->inline()
            ->row([
                Keyboard::inlineButton([
                    'text' => __('tbe-billing::offers.wizard.chooseType.percentage'),
                    'callback_data' => encodeCallback(self::$type, 'setType', [$offer->id, 'percentage', $lastPage, $messageMetaId]),
                ]),
                Keyboard::inlineButton([
                    'text' => __('tbe-billing::offers.wizard.chooseType.fixed'),
                    'callback_data' => encodeCallback(self::$type, 'setType', [$offer->id, 'fixed', $lastPage, $messageMetaId]),
                ]),
            ]);

        return new TelegramResponse(
            text: __('tbe-billing::offers.wizard.chooseType.text'),
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    /** The prompt + optional Skip button for a wizard step, reused for every optional field. */
    public static function optionalFieldPrompt(Offer $offer, string $field, int $lastPage, int $messageMetaId): TelegramResponse
    {
        $replyMarkup = Keyboard::make()->inline()->row([
            Keyboard::inlineButton([
                'text' => __('tbe-billing::offers.wizard.skip'),
                'callback_data' => encodeCallback(self::$type, 'skipOptionalField', [$offer->id, $field, $lastPage, $messageMetaId]),
            ]),
        ]);

        return new TelegramResponse(
            text: __('tbe-billing::offers.wizard.fields.'.$field.'.prompt'),
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
