<?php

return [
    'main' => [
        'text' => [
            'list' => '📋 پیشخوان فاکتورها — برای بررسی، یک فاکتور را انتخاب کنید.',
            'empty' => '😕 هنوز فاکتوری یافت نشد. لطفاً بعداً دوباره امتحان کنید!',
            'waiting_page' => '⌛ در انتظار شماره صفحه.',
            'enter_page' => '🔢 شماره صفحه را وارد کنید:',
            'page_loaded' => '📄 صفحه :page بارگذاری شد.',
            'show' => "🧾 فاکتور #:invoiceId"
                . "\r\n"
                . "\r\n👤 مالک: :invoiceOwner"
                . "\r\n💲 مبلغ: :invoiceAmount"
                . "\r\n📌 وضعیت: :invoiceStatus"
                . "\r\n"
                . "\r\n🕑 آخرین تلاش پرداخت: :paymentAttempt"
                . "\r\n📋 وضعیت تلاش: :paymentAttemptStatus"
                . "\r\n🗓️ تاریخ تلاش: :paymentAttemptDate"
                . "\r\n"
                . "\r\n📝 توضیحات:\r\n:orderDescription"
                . "\r\n"
                . "\r\n⚙️ یک اقدام را از لیست زیر انتخاب کنید 👇",
        ],
        'answers' => [
        ],
        'keys' => [
            'invoice' => ':status #:invoiceId · :resourceName :price | :userFullName',
            'col_id' => 'کاربر',
            'col_type' => 'نوع/تاریخ',
            'col_status' => 'قیمت',
            'status_failed' => '❌ ثبت به‌عنوان ناموفق',
            'status_pending' => '🕒 ثبت به‌عنوان در انتظار',
            'status_paid' => '✅ ثبت به‌عنوان پرداخت شده',
            'status_indicator' => [
                'paid' => '✅ پرداخت شده',
                'pending' => '🕒 در انتظار',
                'failed' => '❌ ناموفق',
            ],
            'back_to_list' => '🔙 بازگشت به فهرست فاکتورها',
        ],
    ],

    'alerts' => [
        'already_paid' => '✅ این فاکتور پیش‌تر پرداخت شده است.',
        'already_pending' => '🕒 وضعیت فاکتور هم‌اکنون «در انتظار» است.',
        'already_failed' => '❌ این فاکتور هم‌اکنون در وضعیت ناموفق قرار دارد.',
    ],

    'reply' => [
        'keys' => [
            'manage_invoices' => [
                'text' => '🧾 مدیریت فاکتورها',
                'response' => '📋 پیشخوان مدیریت فاکتورها با موفقیت باز شد.',
            ],
        ],
    ],
];
