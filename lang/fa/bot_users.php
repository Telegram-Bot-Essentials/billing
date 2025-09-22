<?php

return [
    'main' => [
        'text' => [
            'index' => "مدیریت کاربران ربات"
                . "\r\n"
                . "\r\n"
                . "تعداد کاربران: :userCount\r\n"
                . "کاربران اضافه‌شده در ۲۴ ساعت گذشته: :usersJoinedLastDay\r\n"
                . "مجموع اعتبار کاربران: :totalUserCredits\r\n",
            'show_user' => ":userFullName"
                . "\n\n"
                . "شناسه کاربر در تلگرام: :userPeerId\r\n"
                . "نام کاربری تلگرام: :userUsername\r\n"
                . "نام کوچک تلگرام: :userFirstName\r\n"
                . "نام خانوادگی تلگرام: :userLastName\r\n"
                . "شماره تلفن تلگرام: :userTel\r\n"
                . "\r\n"
                . "نقش: :userRole\r\n"
                . "اعتبار: :userCredit\r\n"
                . "وضعیت: :userSuspendStatus\r\n"
                . "تاریخ عضویت: :userCreatedAt\r\n"
                . "آخرین تعامل: :userUpdatedAt\r\n"
                . "\r\n"
                . "⚠️ این اطلاعات در تاریخ :dataReceiveTime دریافت شده است"
        ],
        'answers' => [
        ],
        'keys' => [
            'user' => ':fullName :suspendStatus - :credit',
            'userIsActive' => '✅ کاربر فعال است',
            'userIsSuspended' => '⛔️ کاربر تعلیق شده است',
            "userRole" => "💪 نقش: :role",
            "userUpdateData" => "♻️ بروزرسانی",
            "setUserBalance" => "💵 تنظیم اعتبار",
            "addUserBalance" => "💸 افزایش اعتبار",
        ]
    ],
    'reply_key' => 'کاربران ربات 👥',
];
