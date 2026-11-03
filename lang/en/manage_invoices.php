<?php

return [
    'main' => [
        'text' => [
            'not_fulfilled' => "\n⚠️ <b>Paid but not delivered:</b> :error",
            'list' => '📋 Invoice dashboard — pick an invoice to review.',
            'empty' => '😕 No invoices yet.',
            'waiting_page' => '⌛ Waiting for the page number.',
            'enter_page' => '🔢 Enter the page number:',
            'page_loaded' => '📄 Page :page loaded.',
            'show' => '🧾 Invoice #:invoiceId'
                ."\r\n"
                ."\r\n👤 Owner: :invoiceOwner"
                ."\r\n💲 Amount: :invoiceAmount"
                ."\r\n📌 Status: :invoiceStatus"
                ."\r\n🏷️ Order type: :orderType"
                ."\r\n"
                ."\r\n🕑 Last payment attempt: :paymentAttempt"
                ."\r\n📋 Attempt status: :paymentAttemptStatus"
                ."\r\n🗓️ Attempt date: :paymentAttemptDate"
                ."\r\n"
                ."\r\n📦 Order details:\r\n:orderDescription"
                ."\r\n"
                ."\r\n⚙️ Choose an action below 👇",
        ],
        'answers' => [
        ],
        'keys' => [
            'invoice' => ':status #:invoiceId · :resourceName :price | :userFullName',
            'col_id' => 'User',
            'col_type' => 'Type/Date',
            'col_status' => 'Price',
            'status_failed' => '❌ Mark as failed',
            'retry_fulfillment' => '🔁 Retry delivery',
            'status_pending' => '🕒 Mark as pending',
            'status_paid' => '✅ Mark as paid',
            'status_indicator' => [
                'paid' => '✅ Paid',
                'pending' => '🕒 Pending',
                'failed' => '❌ Failed',
            ],
            'no_attempt' => '— No payment attempt yet',
            'back_to_list' => '🔙 Back to invoice list',
            'back_to_profile' => '🔙 Back to the profile',
        ],
    ],

    'alerts' => [
        'fulfilled' => 'Delivered.',
        'still_not_fulfilled' => 'Still could not deliver it. See the reason on the invoice.',
        'already_fulfilled' => 'This invoice has nothing waiting to be delivered.',
        'already_paid' => '✅ This invoice is already marked as paid.',
        'already_pending' => '🕒 This invoice is already pending.',
        'already_failed' => '❌ This invoice is already marked as failed.',
    ],

    'reply' => [
        'keys' => [
            'manage_invoices' => [
                'text' => '🧾 Manage invoices',
                'response' => '📋 Invoice manager opened.',
            ],
        ],
    ],
];
