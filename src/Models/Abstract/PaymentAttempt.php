<?php

namespace TelegramBotEssentials\Billing\Models\Abstract;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use TelegramBotEssentials\Billing\Models\Invoice;

abstract class PaymentAttempt extends Model
{
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function invoice(): MorphOne
    {
        return $this->morphOne(Invoice::class, 'payment_attempt');
    }

    public function attemptSucceed(): void
    {
        $this->attemptSucceedHook();
        $this->status = 'succeed';
        $this->save();
        $this->invoice->markAsPaid();
    }

    public function attemptFailed(): void
    {
        $this->attemptFailedHook();
        $this->status = 'failed';
        $this->save();
        $this->invoice->markAsFailed();
    }

    abstract protected function attemptSucceedHook(): void;

    abstract protected function attemptFailedHook(): void;
}
