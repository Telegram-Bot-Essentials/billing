<?php

return [
    'summary' => [
        'text' => [
            'information' => "#⃣ Invoice :invoiceId"
                . "\r\n"
                . "\r\n📝 Description: \r\n:orderDescription"
                . "\r\n"
                . "\r\n👇 Choose your payment option from below.",
            'noPaymentMethods' => "#⃣ Invoice :invoiceId"
                . "\r\n"
                . "\r\n📝 Description: \r\n:orderDescription"
                . "\r\n"
                . "\r\n❌ Currently there is no available payment method, Please try again later.",
        ],
        'answers' => [
            'main' => 'Invoice loaded',
            'created' => 'Invoice created',
            'noPaymentMethods' => 'There is no available payment method',
        ],
        'keys' => [
            'to_card' => 'Pay To Card 💳 - :price تومان',
            'by_wallet' => 'Pay Using wallet 💰 - :price',
            'to_zirgozar' => 'Pay with zirgozar 💰 - :price تومان',
            'to_zarinpal' => 'Pay with zarinpal 💰 - :price تومان',
            'to_zibal' => 'Pay with zibal 💰 - :price تومان',
            'back_to_previous' => 'Back to previous action',
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
        'order_reverted' => 'Your order reverted',
        'status_changed' => [
            'paid' => 'Your invoice status changed to paid',
            'pending' => 'Your invoice status changed to pending',
            'failed' => 'Your invoice status changed to failed',
        ],
    ],
];
