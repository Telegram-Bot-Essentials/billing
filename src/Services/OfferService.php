<?php

namespace TelegramBotEssentials\Billing\Services;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Models\Offer;
use TelegramBotEssentials\Billing\Models\OfferRedemption;

class OfferService
{
    /** The optional fields of an offer, in the order they are assigned (max_price checks min_price). */
    public const OPTIONAL_FIELDS = ['max_discount', 'min_price', 'max_price', 'usage_limit', 'usage_limit_per_user', 'expires_at'];

    /**
     * @throws ValidationException
     */
    public function redeem(Invoice $invoice, string $code): Offer
    {
        if (! ($invoice->payable?->offersAllowed() ?? true)) {
            throw ValidationException::withMessages([
                'code' => __('tbe-billing::offers.redeem.errors.notAllowed'),
            ]);
        }

        $offer = Offer::query()
            ->where('bot_id', $invoice->bot_id)
            ->where('code', mb_strtoupper(trim($code)))
            ->where('is_enabled', true)
            ->first();

        if (! $offer) {
            throw ValidationException::withMessages([
                'code' => __('tbe-billing::offers.redeem.errors.notFound'),
            ]);
        }

        if ($offer->isExpired()) {
            throw ValidationException::withMessages([
                'code' => __('tbe-billing::offers.redeem.errors.expired'),
            ]);
        }

        $basePrice = BigDecimal::of($invoice->original_price ?? $invoice->price);

        if ($offer->min_price !== null && $basePrice->isLessThan($offer->min_price)) {
            throw ValidationException::withMessages([
                'code' => __('tbe-billing::offers.redeem.errors.belowMin', [
                    'min' => currency()->priceFormat($offer->min_price),
                ]),
            ]);
        }

        if ($offer->max_price !== null && $basePrice->isGreaterThan($offer->max_price)) {
            throw ValidationException::withMessages([
                'code' => __('tbe-billing::offers.redeem.errors.aboveMax', [
                    'max' => currency()->priceFormat($offer->max_price),
                ]),
            ]);
        }

        if ($offer->usage_limit !== null && $offer->redemptions()->count() >= $offer->usage_limit) {
            throw ValidationException::withMessages([
                'code' => __('tbe-billing::offers.redeem.errors.exhausted'),
            ]);
        }

        if (
            $offer->usage_limit_per_user !== null &&
            $offer->redemptions()->where('bot_user_id', $invoice->bot_user_id)->count() >= $offer->usage_limit_per_user
        ) {
            throw ValidationException::withMessages([
                'code' => __('tbe-billing::offers.redeem.errors.userExhausted'),
            ]);
        }

        $discount = $this->calculateDiscount($offer, $basePrice);

        $invoice->offer_id = $offer->id;
        $invoice->price = (string) $basePrice->minus($discount);
        $invoice->save();

        return $offer;
    }

    public function remove(Invoice $invoice): void
    {
        if (! $invoice->offer_id) {
            return;
        }

        $invoice->offer_id = null;
        $invoice->price = $invoice->original_price ?? $invoice->price;
        $invoice->save();
    }

    public function calculateDiscount(Offer $offer, BigDecimal $basePrice): BigDecimal
    {
        if ($offer->type === 'percentage') {
            $discount = $basePrice->multipliedBy($offer->amount)->dividedBy(100, scale: $basePrice->getScale(), roundingMode: RoundingMode::HalfUp);

            if ($offer->max_discount !== null) {
                $discount = BigDecimal::min($discount, $offer->max_discount);
            }
        } else {
            $discount = BigDecimal::of($offer->amount);
        }

        return BigDecimal::min($discount, $basePrice);
    }

    /**
     * Turns the offer attached to a now-paid invoice into a confirmed
     * redemption - usage caps are only spent once payment is real, so an
     * abandoned or failed invoice never burns a limited-use code.
     */
    public function confirmRedemption(Invoice $invoice): void
    {
        if (! $invoice->offer_id) {
            return;
        }

        DB::transaction(function () use ($invoice) {
            OfferRedemption::query()->firstOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'offer_id' => $invoice->offer_id,
                    'bot_user_id' => $invoice->bot_user_id,
                    'amount_applied' => (string) BigDecimal::of($invoice->original_price ?? $invoice->price)->minus($invoice->price),
                ]
            );
        });
    }

    /**
     * Frees up the offer's usage slot when a paid invoice is later reverted
     * (refunded, marked failed after the fact, etc).
     */
    public function releaseRedemption(Invoice $invoice): void
    {
        OfferRedemption::query()->where('invoice_id', $invoice->id)->delete();
    }

    public function isCodeTaken(int $botId, string $code): bool
    {
        return Offer::query()->where('bot_id', $botId)->where('code', mb_strtoupper(trim($code)))->exists();
    }

    /**
     * Creates an offer from a finished form's answers (raw strings, as typed),
     * enabled. Optional answers that are null were skipped.
     *
     * @param  array<string, mixed>  $answers
     *
     * @throws ValidationException
     */
    public function createFromAnswers(int $botId, array $answers): Offer
    {
        $offer = new Offer([
            'bot_id' => $botId,
            'code' => (string) $answers['code'],
            'type' => (string) $answers['type'],
            'is_enabled' => true,
        ]);

        $this->assignAmount($offer, (string) $answers['amount']);

        foreach (self::OPTIONAL_FIELDS as $field) {
            $input = $answers[$field] ?? null;

            if ($input !== null) {
                $this->assignField($offer, $field, (string) $input);
            }
        }

        $offer->save();

        return $offer;
    }

    /**
     * @throws ValidationException
     */
    public function parseAmount(string $input, ?string $type): BigDecimal
    {
        $value = $this->parseDecimal($input, 'amount');

        if ($type === 'percentage' && ($value->isLessThanOrEqualTo(0) || $value->isGreaterThan(100))) {
            throw ValidationException::withMessages([
                'amount' => __('tbe-billing::offers.wizard.errors.percentageOutOfRange'),
            ]);
        }

        if ($type === 'fixed' && $value->isLessThanOrEqualTo(0)) {
            throw ValidationException::withMessages([
                'amount' => __('tbe-billing::offers.wizard.errors.mustBePositive'),
            ]);
        }

        return $value;
    }

    /**
     * @throws ValidationException
     */
    public function assignAmount(Offer $offer, string $input): void
    {
        $offer->amount = (string) $this->parseAmount($input, $offer->type);
    }

    /**
     * The typed value of an optional field's input: a decimal for the money
     * fields, a whole number for the limits and the expiry in days.
     *
     * @param  ?string  $minPrice  the min order price already chosen, which a max order price may not undercut
     *
     * @throws ValidationException
     */
    public function parseField(string $field, string $input, ?string $minPrice = null): BigDecimal|int
    {
        return match ($field) {
            'max_discount' => $this->parsePositiveDecimal($input, 'max_discount'),
            'min_price' => $this->parseNonNegativeDecimal($input, 'min_price'),
            'max_price' => $this->parseMaxPrice($input, $minPrice),
            'usage_limit', 'usage_limit_per_user', 'expires_at' => $this->parsePositiveInt($input, $field),
            default => throw new \InvalidArgumentException("Unknown offer field: {$field}"),
        };
    }

    /**
     * @throws ValidationException
     */
    public function assignField(Offer $offer, string $field, string $input): void
    {
        $value = $this->parseField($field, $input, $offer->min_price);

        match ($field) {
            'expires_at' => $offer->expires_at = now()->addDays((int) $value),
            'usage_limit', 'usage_limit_per_user' => $offer->{$field} = (int) $value,
            default => $offer->{$field} = (string) $value,
        };
    }

    public function clearField(Offer $offer, string $field): void
    {
        $offer->{$field} = null;
    }

    /**
     * @throws ValidationException
     */
    private function parseMaxPrice(string $input, ?string $minPrice): BigDecimal
    {
        $value = $this->parseNonNegativeDecimal($input, 'max_price');

        if ($minPrice !== null && $value->isLessThan($minPrice)) {
            throw ValidationException::withMessages([
                'max_price' => __('tbe-billing::offers.wizard.errors.maxBelowMin'),
            ]);
        }

        return $value;
    }

    /**
     * @throws ValidationException
     */
    private function parsePositiveDecimal(string $input, string $field): BigDecimal
    {
        $value = $this->parseDecimal($input, $field);

        if ($value->isLessThanOrEqualTo(0)) {
            throw ValidationException::withMessages([
                $field => __('tbe-billing::offers.wizard.errors.mustBePositive'),
            ]);
        }

        return $value;
    }

    /**
     * @throws ValidationException
     */
    private function parseNonNegativeDecimal(string $input, string $field): BigDecimal
    {
        $value = $this->parseDecimal($input, $field);

        if ($value->isNegative()) {
            throw ValidationException::withMessages([
                $field => __('tbe-billing::offers.wizard.errors.mustNotBeNegative'),
            ]);
        }

        return $value;
    }

    /**
     * @throws ValidationException
     */
    private function parseDecimal(string $input, string $field): BigDecimal
    {
        try {
            return BigDecimal::of(trim($input));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $field => __('tbe-billing::offers.wizard.errors.mustBeNumeric'),
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function parsePositiveInt(string $input, string $field): int
    {
        if (! ctype_digit(trim($input)) || (int) trim($input) < 1) {
            throw ValidationException::withMessages([
                $field => __('tbe-billing::offers.wizard.errors.mustBePositiveInteger'),
            ]);
        }

        return (int) trim($input);
    }
}
