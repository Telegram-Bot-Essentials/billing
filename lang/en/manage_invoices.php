<?php

return [
    'main' => [
        'text' => [
            'list' => 'Invoices',
            'empty' => 'No invoices found',
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
            'status_failed' => 'Failed',
            'status_pending' => 'Pending',
            'status_paid' => 'Paid',
            'status_selected_suffix' => ' ✅',
        ],
    ],

    'alerts' => [
        'already_paid' => 'Invoice is already paid',
        'already_pending' => 'Invoice is already pending',
        'already_failed' => 'Invoice is already failed',
    ],

    'reply' => [
        'keys' => [
            'manage_invoices' => [
                'text' => 'Manage Invoices',
                'response' => 'Manage Invoices executed successfully.',
            ],
        ],
    ],
];
