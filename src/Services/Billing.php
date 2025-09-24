<?php

namespace TelegramBotEssentials\Billing\Services;


use TelegramBotEssentials\Billing\Models\Abstract\PaymentAttempt;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Models\Abstract\Order;

class  Billing
{
    public function createInvoice(Order $order): Invoice
    {
        return $order->invoice()->create([
            'bot_user_id' => $order->botUser->id,
            'price' => $order->amount
        ]);
    }

    public function attemptPayment(Invoice $invoice, PaymentAttempt $paymentAttempt): void
    {
        $invoice->paymentAttempt()->associate($paymentAttempt);
        $invoice->save();
    }
}
