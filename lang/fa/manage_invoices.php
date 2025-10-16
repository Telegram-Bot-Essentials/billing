<?php

return [
    'main' => [
        'text' => [
            'list' => 'فاکتورها',
            'empty' => 'هیچ فاکتوری یافت نشد',
            'show' => "#⃣ فاکتور :invoiceId"
                . "\r\n"
                . "\r\nمالک فاکتور: :invoiceOwner"
                . "\r\nمبلغ فاکتور: :invoiceAmount"
                . "\r\nوضعیت فاکتور: :invoiceStatus"
                . "\r\n"
                . "\r\nآخرین تلاش پرداخت: :paymentAttempt"
                . "\r\nوضعیت آخرین تلاش پرداخت: :paymentAttemptStatus"
                . "\r\nتاریخ آخرین تلاش پرداخت: :paymentAttemptDate"
                . "\r\n"
                . "\r\n📝 توضیحات: \r\n:orderDescription"
                . "\r\n"
                . "\r\n👇 یکی از گزینه‌های پرداخت زیر را انتخاب کنید.",
        ],
        'answers' => [
        ],
        'keys' => [
            'invoice' => '#:invoiceId - :resourceName :price | :userFullName :status',
            'status_failed' => 'ناموفق',
            'status_pending' => 'در انتظار',
            'status_paid' => 'پرداخت شده',
            'status_selected_suffix' => ' ✅',
        ],
    ],

    'alerts' => [
        'already_paid' => 'این فاکتور قبلاً پرداخت شده است',
        'already_pending' => 'این فاکتور هم‌اکنون در وضعیت در انتظار است',
        'already_failed' => 'این فاکتور هم‌اکنون ناموفق است',
    ],

    'reply' => [
        'keys' => [
            'manage_invoices' => [
                'text' => 'مدیریت فاکتورها',
                'response' => 'دستور مدیریت فاکتورها با موفقیت اجرا شد.',
            ],
        ],
    ],
];
