<?php

return [
    'summary' => [
        'text' => [
            'information' => "#⃣ فاکتور :invoiceId"
                . "\r\n"
                . "\r\n📝 توضیحات سفارش: \r\n:orderDescription"
                . "\r\n"
                . "\r\n👇 یکی از روش‌های پرداخت زیر را انتخاب کنید.",
            'noPaymentMethods' => "#⃣ فاکتور :invoiceId"
                . "\r\n"
                . "\r\n📝 توضیحات سفارش: \r\n:orderDescription"
                . "\r\n"
                . "\r\n❌ در حال حاضر هیچ روش پرداختی در دسترس نیست، لطفاً بعداً دوباره تلاش کنید.",
        ],
        'answers' => [
            'main' => '✅ فاکتور بارگذاری شد',
            'created' => '🧾 فاکتور ایجاد شد',
            'noPaymentMethods' => 'هیچ روش پرداختی در دسترس نیست',
        ],
        'keys' => [
            'to_card' => 'کارت به کارت 💳 - :price تومان',
            'by_wallet' => 'پرداخت با کیف پول 💰 - :price',
            'back_to_previous' => '🔙 بازگشت به مرحله قبلی',
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
        'order_reverted' => 'سفارش شما لغو شد',
        'status_changed' => [
            'paid' => 'وضعیت فاکتور شما به پرداخت شده تغییر کرد',
            'pending' => 'وضعیت فاکتور شما به در انتظار تغییر کرد',
            'failed' => 'وضعیت فاکتور شما به ناموفق تغییر کرد',
        ],
    ],
];
