<?php

namespace TelegramBotEssentials\UserManagement\Telegram\Features\Admin;

use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Services\TelegramPaginator;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;
use Telegram\Bot\Keyboard\Keyboard;

class BotUsersFeature
{
    static string $type = 'BOTUSERS';

    /**
     * @throws InvalidPageNumber
     */
    public static function start(int $page = 1, int $currentPage = 0): TelegramResponse
    {
        $text = __('tbe::bot_users.main.text.index', [
            'userCount' => BotUser::count(),
            'usersJoinedLastDay' => BotUser::where('created_at', '>', now()->subDays(1))->count(),
            'totalUserCredits' => currency()->priceFormat(BotUser::sum('balance')),
        ]);
        $users = BotUser::paginate(perPage: 10, page: $page);
        $replyMarkup = Keyboard::make()->inline();

        TelegramPaginator::validatePageNumber($page, $currentPage, $users);

        foreach ($users as $botUser) {
            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => __('tbe::bot_users.main.keys.user', [
                        'fullName' => $botUser->telegramUser->full_name,
                        'credit' => currency()->priceFormat($botUser->balance),
                        'suspendStatus' => $botUser->suspend ? __('tbe::general.status.disabledEmoji') : __('tbe::general.status.enabledEmoji'),
                    ]),
                    'callback_data' => encodeCallback(self::$type, ['show', $botUser->id, $page])
                ])
            ]);
        }

        $replyMarkup->row(TelegramPaginator::makeNavigationButtonsRow(self::$type, $page, $users->lastPage()));

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function show(BotUser $botUser, int $lastPage = 1): TelegramResponse
    {
        $text = $botUser->telegramUser->full_name;

        $text = __('tbe::bot_users.main.text.show_user', [
            'userFullName' => "<a href=\"tg://user?id={$botUser->telegramUser->peer_id}\">{$botUser->telegramUser->full_name}</a>",
            'userPeerId' => $botUser->telegramUser->peer_id,
            'userUsername' => $botUser->telegramUser->username ? '@' . $botUser->telegramUser->username : '',
            'userFirstName' => $botUser->telegramUser->first_name,
            'userLastName' => $botUser->telegramUser->last_name,
            'userTel' => $botUser->telegramUser->tel,
            'userRole' => $botUser->role,
            'userCredit' => currency()->priceFormat($botUser->balance),
            'userSuspendStatus' => $botUser->suspend ?
                __('tbe::general.status.suspended', [
                    'suspendedDate' => $botUser->suspended_at?->format('Y-m-d H:i:s')
                ]) :
                __('tbe::general.status.notSuspended'),
            'userCreatedAt' => $botUser->created_at,
            'userUpdatedAt' => $botUser->updated_at,
            'dataReceiveTime' => now()->format('Y-m-d H:i:s'),
        ]);
        $replyMarkup = Keyboard::make()->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => $botUser->suspend ? __('tbe::bot_users.main.keys.userIsSuspended') : __('tbe::bot_users.main.keys.userIsActive'),
                'callback_data' => encodeCallback(self::$type, ['suspend', $botUser->id, intval(!$botUser->suspend)])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_users.main.keys.userRole', ['role' => $botUser->role]),
                'callback_data' => encodeCallback(self::$type, ['role', $botUser->id, $lastPage])
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe::bot_users.main.keys.userUpdateData'),
                'callback_data' => encodeCallback(self::$type, ['show', $botUser->id, $lastPage])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::bot_users.main.keys.addUserBalance'),
                'callback_data' => encodeCallback(self::$type, ['balance', 'add', $botUser->id, $lastPage])
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe::bot_users.main.keys.setUserBalance'),
                'callback_data' => encodeCallback(self::$type, ['balance', 'set', $botUser->id, $lastPage])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, ['start', $lastPage])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }
}
