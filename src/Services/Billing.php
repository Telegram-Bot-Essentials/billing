<?php

namespace TelegramBotEssentials\Billing\Services;

use TelegramBotEssentials\Billing\Models\Abstract\Order;
use TelegramBotEssentials\Billing\Models\Abstract\PaymentAttempt;
use TelegramBotEssentials\Billing\Models\Invoice;

class Billing
{
    public function createInvoice(Order $order): Invoice
    {
        $invoice = $order->invoice()->create([
            'bot_user_id' => $order->botUser->id,
            'price' => $order->amount,
            'original_price' => $order->amount,
        ]);

        // create() doesn't reload columns left to their DB default (status
        // defaults to 'pending') - refresh so the in-memory model matches
        // the row instead of leaving ->status null until something else
        // reloads it.
        $invoice->refresh();

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
