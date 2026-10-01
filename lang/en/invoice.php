<?php

return [
    'summary' => [
        'text' => [
            'information' => '🧾 Invoice #:invoiceId'
                ."\r\n"
                ."\r\n📝 Description:\r\n:orderDescription"
                ."\r\n"
                ."\r\n💳 Choose your preferred payment option 👇",
            'noPaymentMethods' => '🧾 Invoice #:invoiceId'
                ."\r\n"
                ."\r\n📝 Description:\r\n:orderDescription"
                ."\r\n"
                ."\r\n🚧 No payment methods are available right now. Please try again soon ✨",
        ],
        'answers' => [
            'main' => '🧾 Invoice ready',
            'created' => '🎉 Invoice created',
            'noPaymentMethods' => '🚧 No payment method is currently available',
        ],
        'keys' => [
            'to_card' => 'Pay by card 💳 - :price تومان',
            'by_wallet' => 'Pay with wallet 💰 - :price',
            'to_zarinpal' => 'Pay with Zarinpal 💰 - :price تومان',
            'to_zibal' => 'Pay with Zibal 💰 - :price تومان',
            'back_to_previous' => '🔙 Go back',
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

    'payment' => [
        'amount' => '💰 Amount to pay: :price',
    ],

    'offer' => [
        'summary' => '🏷️ Offer code <b>:code</b> applied — <s>:originalPrice</s> :price',
        'prompt' => '🏷️ Send the offer code you want to use:',
        'applied' => '✅ Offer code applied.',
        'removed' => '🚫 Offer code removed.',
        'lockLabel' => 'Applying offer code…',
        'keys' => [
            'use' => '🏷️ Use offer code',
            'remove' => '🚫 Remove code (:code)',
        ],
    ],

    'hooks' => [
        'order_reverted' => '🛑 Your order has been reverted.',
        'status_changed' => [
            'paid' => '✅ Good news! Your invoice is now paid.',
            'pending' => '🕒 Your invoice is currently pending review.',
            'failed' => '❌ Your invoice couldn\'t be processed.',
        ],
    ],

    'locks' => [
        'user_payment' => [
            'accepted' => '✅ Payment received — thank you!',
            'rejected' => '⚠️ Payment was declined. Please retry or choose another method.',
            'cancelled' => '🚫 Payment was cancelled. Start a new attempt whenever you are ready.',
        ],
    ],
];
