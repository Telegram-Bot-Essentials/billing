<?php

namespace TelegramBotEssentials\Billing\Models\Attempts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TelegramBotEssentials\Billing\Models\Abstract\PaymentAttempt;
use TelegramBotEssentials\Billing\Models\Payment;
use TelegramBotEssentials\Essence\Traits\HasMessageMeta;

class ByWalletAttempt extends PaymentAttempt
{
    use HasMessageMeta;
    protected $guarded = [
        'id',
        'updated_at',
        'deleted_at',
        'created_at',
    ];

    public function attempt(): void
    {
        if($this->paymentAttempt->invoice->botUser->balance < $this->paymentAttempt->invoice->price) return;
        $this->paymentAttempt->invoice->botUser->balance -= $this->paymentAttempt->invoice->price;
        $this->paymentAttempt->invoice->botUser->save();
        $this->received_at = now();
        $this->save();
        $this->paymentAttempt->invoice->triggerInvoicePaidHook();
        $this->paymentAttempt->invoice->messageMeta->lockAction(__('tbe::invoice.to_card.lock-keys.user-payment_accepted'), customEmoji: "✅");
    }

    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    protected function attemptSucceedHook(): void
    {
        // TODO: Implement attemptSucceedHook() method.
    }

    protected function attemptFailedHook(): void
    {
        // TODO: Implement attemptFailedHook() method.
    }
}
