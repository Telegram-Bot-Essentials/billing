<?php

namespace TelegramBotEssentials\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use TelegramBotEssentials\Billing\Jobs\InvoiceFailedHookJob;
use TelegramBotEssentials\Billing\Jobs\InvoicePaidHookJob;
use TelegramBotEssentials\Billing\Jobs\InvoicePendingHookJob;
use TelegramBotEssentials\Essence\Database\factories\InvoiceFactory;
use TelegramBotEssentials\Billing\Jobs\CancelOrderHookJob;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Traits\HasMessageMeta;

class Invoice extends Model
{
    use SoftDeletes;
    use BelongsToTenant;
    use HasFactory;
    use HasMessageMeta;

    protected $guarded = [
        'id',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function ($invoice) {
            if ($invoice->payable_type && $invoice->payable_id) {
                self::where('payable_type', $invoice->payable_type)
                    ->where('payable_id', $invoice->payable_id)
                    ->whereNull('deleted_at')
                    ->delete();
            }
        });
    }

    public function getPublicTokenAttribute(): ?string
    {
        if (empty($this->attributes['public_token'] ?? null)) {
            $publicToken = uniqid();
            $this->attributes['public_token'] = $publicToken;
            $this->save();
        }
        return $this->attributes['public_token'];
    }

    public function paymentAttempt(): MorphTo
    {
        return $this->morphTo();
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function setStatusAttribute($value): void
    {
        if($this->status != $value && $this->status == 'paid') {
            dispatch(new CancelOrderHookJob(wHook()->api(), wHook()->update(), wHook()->bot(), wHook()->user(), $this));
        }
        $this->attributes['status'] = $value;
    }

    public function geStatusAttribute(): string
    {
        return $this->status;
    }

    public function markAsPaid(): void
    {
        $this->setAttribute('status', 'paid');
        $this->save();
        dispatch(new InvoicePaidHookJob(wHook()->api(), wHook()->update(), wHook()->bot(), wHook()->user(), $this));
    }

    public function markAsFailed(): void
    {
        $this->setAttribute('status', 'failed');
        $this->save();
        dispatch(new InvoiceFailedHookJob(wHook()->api(), wHook()->update(), wHook()->bot(), wHook()->user(), $this));
    }

    public function markAsPending(): void
    {
        $this->setAttribute('status', 'pending');
        $this->save();
        dispatch(new InvoicePendingHookJob(wHook()->api(), wHook()->update(), wHook()->bot(), wHook()->user(), $this));
    }
}
