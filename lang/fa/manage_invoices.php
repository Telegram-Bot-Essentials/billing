<?php

return [
    'main' => [
        'text' => [
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
        ],
    ],
];
