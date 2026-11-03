<?php

namespace TelegramBotEssentials\Billing\Services;

use Illuminate\Support\Collection;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Contracts\RecipientGroups;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use Throwable;

/**
 * Tells the bot's staff about a purchase, when the owner asked for that. Who
 * hears is a setting: any mix of the owner and the bot's recipient groups, which
 * another package may bring. When that reaches nobody it falls back to the owner,
 * so a sale is never announced to no one.
 */
class PurchaseAlerts
{
    public const TARGET_OWNER = 'owner';

    /** @return array<string, string> setting options: the owner, then each group the bot has */
    public function targets(): array
    {
        $groups = wHook()->hasBot() ? app(RecipientGroups::class)->options(wHook()->bot()) : [];

        return [self::TARGET_OWNER => __('tbe-billing::settings.options.purchase_alerts_owner')] + $groups;
    }

    public function send(Invoice $invoice): void
    {
        $text = $this->text($invoice);

        $targets = settings()->get('billing.purchase_alerts.targets');
        $targets = is_array($targets) ? array_map('strval', $targets) : [];

        $recipients = $this->groupMembers($invoice, $targets);

        // notifyUser() contains each failure: one admin who blocked the bot
        // does not cost the others the alert.
        foreach ($recipients as $recipient) {
            notifyUser($recipient, $text, ['parse_mode' => 'HTML']);
        }

        if ($recipients->isEmpty() || in_array(self::TARGET_OWNER, $targets, true)) {
            $this->tellOwner($invoice, $text, $recipients);
        }
    }

    /**
     * @param  list<string>  $targets
     * @return Collection<int, BotUser>
     */
    private function groupMembers(Invoice $invoice, array $targets): Collection
    {
        $bot = $invoice->bot;
        $groupIds = array_values(array_diff($targets, [self::TARGET_OWNER]));

        return $bot instanceof Bot && $groupIds !== []
            ? app(RecipientGroups::class)->members($bot, $groupIds)
            : collect();
    }

    /**
     * @param  Collection<int, BotUser>  $alreadyTold
     */
    private function tellOwner(Invoice $invoice, string $text, Collection $alreadyTold): void
    {
        $bot = $invoice->bot;
        $peerId = $bot instanceof Bot ? $bot->getAttribute('bot_owner_peer_id') : null;

        // An owner may be in a targeted group too: one message, not two.
        if ($peerId === null || $alreadyTold->contains(fn (BotUser $user) => (string) $user->getAttribute('telegram_user_peer_id') === (string) $peerId)) {
            return;
        }

        isolated('alert owner of purchase', fn () => wHook()->api()->sendMessage([
            'chat_id' => $peerId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]));
    }

    private function text(Invoice $invoice): string
    {
        $buyer = $invoice->botUser?->telegramUser;

        try {
            $description = $invoice->payable?->description ?? '—';
        } catch (Throwable) {
            $description = '—';
        }

        return __('tbe-billing::invoice.alerts.purchase', [
            'invoiceId' => $invoice->getKey(),
            'buyer' => $buyer === null ? '—' : '<a href="tg://user?id='.$buyer->peer_id.'">'.e($buyer->full_name).'</a>',
            'amount' => currency()->priceFormat($invoice->price),
            'description' => $description,
        ]);
    }
}
