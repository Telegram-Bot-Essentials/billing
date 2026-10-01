<?php

namespace TelegramBotEssentials\Billing\Telegram\StateAnswers\Admin;

use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Billing\Telegram\Features\Admin\ManageInvoicesFeature;
use TelegramBotEssentials\Essence\Enums\AllowableFields;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Services\TelegramPaginator;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;

class ManageInvoicesAnswer extends StateAnswer
{
    protected string $type = 'MANAGEINVOICES';

    protected int $perm = Roles::ADMIN->value;

    protected array $allowedFields = [
        AllowableFields::TEXT->value,
    ];

    /**
     * @throws LogicException
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     * @throws InvalidPageNumber
     */
    public function setStartPage(string $sortBy = 'id', string $sortDir = 'desc', int $userId = 0): void
    {
        $page = wHook()->update()->message->text;
        $lastPage = Invoice::query()->when($userId, fn ($query) => $query->where('bot_user_id', $userId))->paginate(perPage: 10)->lastPage();

        TelegramPaginator::validatePageInput($page, $lastPage);

        $data = ManageInvoicesFeature::menu(intval($page), sortBy: $sortBy, sortDir: $sortDir, userId: $userId);

        wHook()->user()->changeState();
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => __('tbe-billing::manage_invoices.main.text.page_loaded', ['page' => $page]),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $this->requireMessageMeta()->updateAndContinueAction($data);
    }
}
