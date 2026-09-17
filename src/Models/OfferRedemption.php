<?php

namespace TelegramBotEssentials\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TelegramBotEssentials\Billing\Database\Factories\OfferRedemptionFactory;
use TelegramBotEssentials\Essence\Models\BotUser;

class OfferRedemption extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'amount_applied' => 'string',
    ];

    public static function newFactory(): OfferRedemptionFactory
    {
        return OfferRedemptionFactory::new();
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class);
    }
}
