<?php

return [
    'main' => [
        'text' => [
            'list' => '📋 Invoice dashboard — pick an invoice to review.',
            'empty' => '😕 No invoices found yet. Check back soon!',
            'show' => "🧾 Invoice #:invoiceId"
                . "\r\n"
                . "\r\n👤 Owner: :invoiceOwner"
                . "\r\n💲 Amount: :invoiceAmount"
                . "\r\n📌 Status: :invoiceStatus"
                . "\r\n"
                . "\r\n🕑 Last payment attempt: :paymentAttempt"
                . "\r\n📋 Attempt status: :paymentAttemptStatus"
                . "\r\n🗓️ Attempt date: :paymentAttemptDate"
                . "\r\n"
                . "\r\n📝 Notes:\r\n:orderDescription"
                . "\r\n"
                . "\r\n⚙️ Choose an action below 👇",
        ],
        'answers' => [
        ],
        'keys' => [
            'invoice' => ':status #:invoiceId · :resourceName :price | :userFullName',
            'status_failed' => '❌ Mark as Failed',
            'status_pending' => '🕒 Mark as Pending',
            'status_paid' => '✅ Mark as Paid',
            'status_indicator' => [
                'paid' => '✅ Paid',
                'pending' => '🕒 Pending',
                'failed' => '❌ Failed',
            ],
            'back_to_list' => '🔙 Back to invoice list',
        ],
    ],

    'alerts' => [
        'already_paid' => '✅ This invoice is already marked as paid.',
        'already_pending' => '🕒 This invoice is already pending.',
        'already_failed' => '❌ This invoice is already marked as failed.',
    ],

    'reply' => [
        'keys' => [
            'manage_invoices' => [
                'text' => '🧾 Manage invoices',
                'response' => '📋 Invoice manager opened successfully.',
            ],
        ],
    ],
];
