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
            'creditIsNotEnough' => '⛔️ Your credit is not enough for paying this invoice.'
            . "\r\nYour credit: :credit"
            . "\r\nNeeded credit: :neededCredit",
            ],
        'keys' => [
            ],
    ],

    'to_card' => [
        'text' => [
            'admin-payment_result' => "#⃣ Invoice :invoiceId"
                . "\r\n🏷 New to card Payment"
                . "\r\n"
                . "\r\n📝 Description: \r\n:invoiceDescription"
                . "\r\n"
                . "\r\n📝 User payment description: \r\n:paymentDescription",
            'admin_payment_rejection' => "🔏 Rejecting Card Payment :toCardAttemptId"
                . "\r\n"
                . "\r\nEnter your rejection reason below:",
            'admin-payment_rejected' => "🛑 Reason sent & payment rejected successfully.",

            'user-payment_result' => 'Payment submitted successfully, wait for processing',
            'user-pay_message' => 'Pay the amount to this card then send the result here for processing'
                . "\r\n"
                . "\r\n🔸 :cardNumber"
                . "\r\n :cardName",
            'user-payment_rejected' => "❌ Your payment rejected due to reason below:"
                . "\r\n:rejectionReason",
        ],
        'answers' => [
            'admin-rejecting_payment' => "initializing for rejection",
            'admin-payment_accepted' => "Payment accepted",

            'attempting' => 'Initializing card payment...',
        ],
        'keys' => [
            'admin-accept_payment' => '✅ Accept Payment',
            'admin-reject_payment' => '❌ Reject Payment',
        ],
        'lock-keys' => [
            'admin-rejecting_payment' => "Waiting for rejection answer",
            'admin-payment_accepted_by' => 'Payment accepted By :adminName',
            'admin-payment_rejected_by' => 'Payment rejected By :adminName',

            'user-payment_accepted' => 'Payment accepted',
            'user-payment_rejected' => 'Payment rejected',
            'user-waiting_for_payment' => 'Waiting for payment',
            'user-wait_for_payment_processing' => 'Waiting for payment processing',
        ]
    ],
];
