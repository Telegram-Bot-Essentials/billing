<?php

return [
    'main' => [
        'text' => [
            'list' => '🏷️ Offer codes — pick one to manage.',
            'empty' => '😕 No offer codes yet.',
            'show' => '🏷️ Offer <b>:code</b>'
                ."\r\n"
                ."\r\n💲 Discount: :amount"
                ."\r\n📛 Max discount: :maxDiscount"
                ."\r\n📉 Min order price: :minPrice"
                ."\r\n📈 Max order price: :maxPrice"
                ."\r\n🔢 Global usage limit: :usageLimit"
                ."\r\n👤 Per-user usage limit: :usageLimitPerUser"
                ."\r\n📅 Expires: :expiresAt"
                ."\r\n📌 Status: :status"
                ."\r\n🎟️ Redeemed: :redeemed time(s)"
                ."\r\n"
                ."\r\n⚙️ Choose an action below 👇",
            'stats' => '📊 Usage for <b>:code</b>'
                ."\r\n"
                ."\r\n🎟️ Redeemed :count time(s)"
                ."\r\n💰 Total discount given: :total"
                ."\r\n"
                ."\r\n🕑 Recent redemptions:"
                ."\r\n:recent",
            'noRedemptions' => 'No redemptions yet.',
        ],
        'keys' => [
            'create' => '➕ New offer code',
            'enable' => '✅ Enable',
            'disable' => '🚫 Disable',
            'delete' => '🗑️ Delete',
            'stats' => '📊 Usage stats',
            'clear' => '↩️ Clear',
            'back_to_list' => '🔙 Back to offers',
            'back_to_offer' => '🔙 Back to offer',
        ],
        'answers' => [
            'enabled' => '✅ Offer enabled.',
            'disabled' => '🚫 Offer disabled.',
            'deleted' => '🗑️ Offer deleted.',
            'updated' => '✅ Offer updated.',
        ],
        'type' => [
            'percentage' => 'Percentage',
            'fixed' => 'Fixed amount',
        ],
        'unlimited' => 'Unlimited',
        'never' => 'Never',
        'enabled' => '✅ Enabled',
        'disabled' => '🚫 Disabled',
    ],

    'alerts' => [
        'cannotDeleteHasRedemptions' => '⚠️ This offer has already been redeemed and cannot be deleted — disable it instead.',
    ],

    'wizard' => [
        'lockLabel' => 'Creating offer code…',
        'waitingPage' => '⌛ Waiting for page number.',
        'enterPage' => '🔢 Enter page number:',
        'pageLoaded' => '📄 Page :page loaded.',
        'finished' => '🎉 Offer code created and enabled!',
        'summary' => '🏷️ Review the new offer code',
        'chooseType' => [
            'percentage' => '% Percentage',
            'fixed' => '💵 Fixed amount',
        ],
        'fields' => [
            'code' => [
                'label' => 'Code',
                'prompt' => '🏷️ Send the offer code text (e.g. SAVE20):',
            ],
            'type' => [
                'label' => 'Type',
                'prompt' => '🏷️ Is this a percentage or a fixed-amount discount?',
            ],
            'amount' => [
                'label' => 'Discount amount',
                'editLabel' => '✏️ Edit discount',
                'prompt' => [
                    'percentage' => '💲 Enter the discount percentage (1-100):',
                    'fixed' => '💲 Enter the flat discount amount:',
                ],
            ],
            'max_discount' => [
                'label' => 'Max discount cap',
                'editLabel' => '✏️ Edit max discount cap',
                'prompt' => '📛 Enter the max discount amount this code can ever give, or tap Skip for no cap:',
            ],
            'min_price' => [
                'label' => 'Minimum order price',
                'editLabel' => '✏️ Edit min order price',
                'prompt' => '📉 Enter the minimum order price required to use this code, or tap Skip for no minimum:',
            ],
            'max_price' => [
                'label' => 'Maximum order price',
                'editLabel' => '✏️ Edit max order price',
                'prompt' => '📈 Enter the maximum order price this code can apply to, or tap Skip for no maximum:',
            ],
            'usage_limit' => [
                'label' => 'Global usage limit',
                'editLabel' => '✏️ Edit global usage limit',
                'prompt' => '🔢 Enter how many times this code can be used in total, or tap Skip for unlimited:',
            ],
            'usage_limit_per_user' => [
                'label' => 'Per-user usage limit',
                'editLabel' => '✏️ Edit per-user usage limit',
                'prompt' => '👤 Enter how many times a single user can use this code, or tap Skip for unlimited:',
            ],
            'expires_at' => [
                'label' => 'Expiry',
                'editLabel' => '✏️ Edit expiry',
                'prompt' => '📅 Enter how many days from now this code should expire, or tap Skip for never:',
            ],
        ],
        'errors' => [
            'codeTaken' => '⚠️ That code is already in use — pick another.',
            'percentageOutOfRange' => '⚠️ A percentage discount must be between 1 and 100.',
            'mustBePositive' => '⚠️ Enter a number greater than 0.',
            'mustNotBeNegative' => '⚠️ Enter a number that is not negative.',
            'mustBeNumeric' => '⚠️ Enter a valid number.',
            'mustBePositiveInteger' => '⚠️ Enter a whole number of 1 or more.',
            'maxBelowMin' => '⚠️ The max order price cannot be lower than the min order price already set.',
        ],
    ],

    'redeem' => [
        'errors' => [
            'notFound' => '❌ That offer code was not found.',
            'expired' => '❌ That offer code has expired.',
            'belowMin' => '❌ This code needs an order of at least :min.',
            'aboveMax' => '❌ This code only applies to orders up to :max.',
            'exhausted' => '❌ This offer code has already reached its usage limit.',
            'userExhausted' => '❌ You have already used this offer code the maximum number of times.',
            'notAllowed' => '❌ Offer codes cannot be used on this order.',
        ],
    ],

    'reply' => [
        'keys' => [
            'offers' => [
                'text' => '🏷️ Offer codes',
                'response' => '📋 Offer manager opened successfully.',
            ],
        ],
    ],
];
