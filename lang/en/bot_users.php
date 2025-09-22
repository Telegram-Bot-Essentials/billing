<?php

return [
    'main' => [
        'text' => [
            'index' => "Manage bot users"
            . "\r\n"
            . "\r\n"
            . "User count: :userCount\r\n"
            . "Users joined in the last 24 hours: :usersJoinedLastDay\r\n"
            . "Total user credits: :totalUserCredits\r\n",
            'show_user' => ":userFullName"
                . "\n\n"
                . "Telegram User ID: :userPeerId\r\n"
                . "Telegram User Name: :userUsername\r\n"
                . "Telegram First Name: :userFirstName\r\n"
                . "Telegram Last Name: :userLastName\r\n"
                . "Telegram phone number: :userTel\r\n"
                . "\r\n"
                . "Role: :userRole\r\n"
                . "Credit: :userCredit\r\n"
                . "Status: :userSuspendStatus\r\n"
                . "Joined At: :userCreatedAt\r\n"
                . "Last Interaction: :userUpdatedAt\r\n"
                . "\r\n"
                . "⚠️ This data received at: :dataReceiveTime"
        ],
        'answers' => [
        ],
        'keys' => [
            'user' => ':fullName :suspendStatus - :credit',
            'userIsActive' => '✅ User is active',
            'userIsSuspended' => '⛔️ User is suspended',
            "userRole" => "💪 Role: :role",
            "userUpdateData" => "♻️ Update",
            "setUserBalance" => "💵 Set Balance",
            "addUserBalance" => "💸 Add Balance",
        ]
    ],
    'reply_key' => 'Bot Users 👥',
];
