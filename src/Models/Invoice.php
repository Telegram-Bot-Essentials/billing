<?php

namespace TelegramBotEssentials\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use TelegramBotEssentials\Billing\Events\InvoiceFailed;
use TelegramBotEssentials\Billing\Events\InvoiceFulfillmentFailed;
use TelegramBotEssentials\Billing\Events\InvoicePaid;
use TelegramBotEssentials\Billing\Events\InvoicePending;
use TelegramBotEssentials\Billing\Events\InvoiceRevoked;
use TelegramBotEssentials\Essence\Database\factories\InvoiceFactory;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Traits\HasMessageMeta;
use Throwable;

class Invoice extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use HasMessageMeta;
    use SoftDeletes;

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

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function offerRedemption(): HasOne
    {
        return $this->hasOne(OfferRedemption::class);
    }

    public function setStatusAttribute($value): void
    {
        $previousStatus = $this->attributes['status'] ?? null;

        if ($previousStatus === $value) {
            $this->attributes['status'] = $value;

            return;
        }

        if ($previousStatus === 'paid' && $value !== 'paid') {
            tbeLog('billing')->for($this->botUser)->info('Invoice #{invoice_id} revoked: paid -> {new_status}', [
                'invoice_id' => $this->getKey(),
                'price' => $this->price,
                'new_status' => $value,
            ]);
            event(new InvoiceRevoked($this, $previousStatus));
        }

        $this->attributes['status'] = $value;
    }

    public function geStatusAttribute(): string
    {
        return $this->status;
    }

    public function markAsPaid(): void
    {
        $previousStatus = $this->status;
        if ($previousStatus === 'paid') {
            return;
        }

        $this->setAttribute('status', 'paid');
        $this->save();
        $this->refresh();

        tbeLog('billing')->for($this->botUser)->info('Invoice #{invoice_id} paid: {price}', [
            'invoice_id' => $this->getKey(),
            'price' => $this->price,
            'previous_status' => $previousStatus,
            'payable_type' => $this->payable_type,
            'payable_id' => $this->payable_id,
        ]);

        wHook()->runForUser($this->botUser, function () use ($previousStatus) {
            event(new InvoicePaid($this, $previousStatus));
        });
    }

    /**
     * Deliver what this invoice paid for, recording how it went. Never throws:
     * a paid invoice stays paid, and a failure is there to see and to retry.
     */
    public function fulfill(): bool
    {
        return isolated('fulfill invoice #'.$this->getKey(), function () {
            $payable = $this->payable;

            if ($payable && method_exists($payable, 'invoicePaidHook')) {
                $payable->invoicePaidHook();
            }

            $this->forceFill(['fulfilled_at' => now(), 'fulfillment_error' => null])->save();
        }, onFail: function (Throwable $e) {
            $this->forceFill(['fulfilled_at' => null, 'fulfillment_error' => mb_substr($e->getMessage(), 0, 1000)])->save();

            event(new InvoiceFulfillmentFailed($this));
        });
    }

    /** Paid for, but not delivered: for an admin to retry. */
    public function needsFulfillment(): bool
    {
        return $this->getAttribute('status') === 'paid' && $this->getAttribute('fulfilled_at') === null;
    }

    /**
     * Parameters that make a message to the member quote their invoice message.
     * Empty when there is nothing to quote; one deleted since is not an error,
     * Telegram sends it as a plain message.
     *
     * @return array{reply_parameters?: array{message_id: int, allow_sending_without_reply: bool}}
     */
    public function replyToView(): array
    {
        $messageId = $this->messageMeta()
            ->where('tag', 'invoice_view')
            ->where('chat_id', $this->botUser->telegramUser->peer_id)
            ->latest('id')
            ->value('message_id');

        if (! $messageId) {
            return [];
        }

        return ['reply_parameters' => ['message_id' => (int) $messageId, 'allow_sending_without_reply' => true]];
    }

    public function markAsFailed(): void
    {
        $previousStatus = $this->status;
        if ($previousStatus === 'failed') {
            return;
        }

        $this->setAttribute('status', 'failed');
        $this->save();
        $this->refresh();

        tbeLog('billing')->for($this->botUser)->info('Invoice #{invoice_id} failed (was {previous_status})', [
            'invoice_id' => $this->getKey(),
            'price' => $this->price,
            'previous_status' => $previousStatus,
            'payable_type' => $this->payable_type,
            'payable_id' => $this->payable_id,
        ]);

        wHook()->runForUser($this->botUser, function () use ($previousStatus) {
            event(new InvoiceFailed($this, $previousStatus));
        });
    }

    public function markAsPending(): void
    {
        $previousStatus = $this->status;
        if ($previousStatus === 'pending') {
            return;
        }

        $this->setAttribute('status', 'pending');
        $this->save();
        $this->refresh();

        tbeLog('billing')->for($this->botUser)->info('Invoice #{invoice_id} pending (was {previous_status})', [
            'invoice_id' => $this->getKey(),
            'price' => $this->price,
            'previous_status' => $previousStatus,
            'payable_type' => $this->payable_type,
            'payable_id' => $this->payable_id,
        ]);

        wHook()->runForUser($this->botUser, function () use ($previousStatus) {
            event(new InvoicePending($this, $previousStatus));
        });
    }
}
