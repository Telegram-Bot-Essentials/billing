<?php

return [
    'summary' => [
        'text' => [
            'information' => "🧾 Invoice #:invoiceId"
                . "\r\n"
                . "\r\n📝 Description:\r\n:orderDescription"
                . "\r\n"
                . "\r\n💳 Choose your preferred payment option 👇",
            'noPaymentMethods' => "🧾 Invoice #:invoiceId"
                . "\r\n"
                . "\r\n📝 Description:\r\n:orderDescription"
                . "\r\n"
                . "\r\n🚧 No payment methods are available right now. Please try again soon ✨",
        ],
        'answers' => [
            'main' => '🧾 Invoice loaded successfully',
            'created' => '🎉 Invoice created',
            'noPaymentMethods' => '🚧 No payment method is currently available',
        ],
        'keys' => [
            'to_card' => 'Pay To Card 💳 - :price تومان',
            'by_wallet' => 'Pay Using wallet 💰 - :price',
            'to_zirgozar' => 'Pay with zirgozar 💰 - :price تومان',
            'to_zarinpal' => 'Pay with zarinpal 💰 - :price تومان',
            'to_zibal' => 'Pay with zibal 💰 - :price تومان',
            'back_to_previous' => '🔙 Back to previous action',
        ],
    ],

    'by_wallet' => [
        'text' => [
            ],
        'answers' => [
            ],
        'keys' => [
            ],
    ],

    'hooks' => [
        'order_reverted' => '🛑 Your order has been reverted.',
        'status_changed' => [
            'paid' => '✅ Good news! Your invoice is now paid.',
            'pending' => '🕒 Your invoice is currently pending review.',
            'failed' => '❌ Your invoice unfortunately failed to process.',
        ],
    ],

    'locks' => [
        'user_payment' => [
            'accepted' => '✅ Payment locked in — thank you for settling the invoice!',
            'rejected' => '⚠️ Payment was declined. Please retry or choose another method.',
            'cancelled' => '🚫 Payment was cancelled. Start a new attempt whenever you are ready.',
        ],
    ],
];
