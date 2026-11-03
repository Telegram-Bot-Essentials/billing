<?php

return [
    'labels' => [
        'billing' => 'Billing',
        'gateways' => 'Gateways',
        'currency' => 'Currency',
        'purchase_alerts' => 'Purchase alerts',
        'purchase_alerts_status' => 'Alert on every purchase',
        'purchase_alerts_target' => 'Who gets them',
    ],

    'options' => [
        'purchase_alerts_owner' => 'Bot owner',
    ],

    'descriptions' => [
        'billing' => 'Payment and invoicing settings for the bot.',
        'gateways' => 'Enable and configure the payment methods customers can use.',
        'currency' => 'The currency used to display and charge prices.',
        'purchase_alerts' => 'Tell your staff when someone buys something.',
        'purchase_alerts_status' => 'Send an alert for every paid invoice. Free orders and prizes are left out.',
        'purchase_alerts_target' => 'Everyone holding this role gets the alert. If nobody does, it goes to the owner.',
    ],
];
