<?php

namespace TelegramBotEssentials\Billing\Models\Abstract;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Billing\Traits\HasInvoice;
use TelegramBotEssentials\Essence\Traits\HasMessageMeta;

abstract class Order extends Model
{
    use BelongsToTenant;
    use HasMessageMeta;
    use HasInvoice;

    protected $appends = ['amount', 'description', 'paid_at'];
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class);
    }

    abstract public function getPaidAtAttribute(): ?Carbon;

    abstract public function getAmountAttribute(): string;

    abstract public function getDescriptionAttribute(): string;
}
