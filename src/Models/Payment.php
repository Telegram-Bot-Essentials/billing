<?php

namespace TelegramBotEssentials\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use TelegramBotEssentials\Essence\Traits\HasMessageMeta;

// TODO: remove this model
class Payment extends Model
{
    use HasMessageMeta;

    protected $appends = ['paid_at', 'failed_at'];
    protected $fillable = ['payment_id', 'attemptable_type', 'attemptable_id', 'status'];

    public function getPaidAtAttribute(): ?Carbon
    {
        $toCardAttempt = $this->toCardAttempt;
        return $toCardAttempt?->accepted_at;
    }

    public function getFailedAtAttribute(): ?Carbon
    {
        $toCardAttempt = $this->toCardAttempt;
        return $toCardAttempt?->rejected_at;
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function attempt(): MorphTo
    {
        return $this->morphTo();
    }
}

