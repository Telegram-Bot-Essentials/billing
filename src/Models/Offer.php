<?php

namespace TelegramBotEssentials\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use TelegramBotEssentials\Billing\Database\Factories\OfferFactory;
use TelegramBotEssentials\Essence\Models\Bot;

class Offer extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'amount' => 'string',
        'max_discount' => 'string',
        'min_price' => 'string',
        'max_price' => 'string',
        'is_enabled' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = mb_strtoupper(trim($value));
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public static function newFactory(): OfferFactory
    {
        return OfferFactory::new();
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(OfferRedemption::class);
    }
}
