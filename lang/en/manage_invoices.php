<?php

return [
    'main' => [
        'text' => [
            'show' => "#⃣ Invoice :invoiceId"
                . "\r\n"
                . "\r\nInvoice Owner: :invoiceOwner"
                . "\r\nInvoice Amount: :invoiceAmount"
                . "\r\nInvoice Status: :invoiceStatus"
                . "\r\n"
                . "\r\nLast Payment Attempt: :paymentAttempt"
                . "\r\nLast Payment Attempt Status: :paymentAttemptStatus"
                . "\r\nLast Payment Attempt Date: :paymentAttemptDate"
                . "\r\n"
                . "\r\n📝 Description: \r\n:orderDescription"
                . "\r\n"
                . "\r\n👇 Choose your payment option from below.",
        ],
        'answers' => [
        ],
        'keys' => [
            'invoice' => '#:invoiceId - :resourceName :price | :userFullName :status',
        ],
    ],
];
