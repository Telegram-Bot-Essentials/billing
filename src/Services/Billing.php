<?php

namespace TelegramBotEssentials\Billing\Services;


use TelegramBotEssentials\Billing\Models\Abstract\PaymentAttempt;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Models\Abstract\Order;

class  Billing
{
    public function createInvoice(Order $order): Invoice
    {
        $invoice = $order->invoice()->create([
            'bot_user_id' => $order->botUser->id,
            'price' => $order->amount
        ]);

        tbeLog('billing')->info('Invoice created', [
            'invoice_id' => $invoice->getKey(),
            'price' => $invoice->price,
            'order_type' => get_class($order),
            'order_id' => $order->getKey(),
        ]);

        return $invoice;
    }

    public function attemptPayment(Invoice $invoice, PaymentAttempt $paymentAttempt): void
    {
        $invoice->paymentAttempt()->associate($paymentAttempt);
        $invoice->save();

        tbeLog('billing')->info('Payment attempt associated', [
            'invoice_id' => $invoice->getKey(),
            'attempt_type' => get_class($paymentAttempt),
            'attempt_id' => $paymentAttempt->getKey(),
        ]);
    }
}
