<?php

namespace TelegramBotEssentials\Billing\Services;

use Illuminate\Support\Collection;
use TelegramBotEssentials\Access\Models\Role;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use Throwable;

/**
 * Tells the bot's staff about a purchase, when the owner asked for that. Who
 * hears is a setting: a role from tbe-access, or the owner. A role nobody holds
 * any more falls back to the owner, so a sale is never announced to no one.
 */
class PurchaseAlerts
{
    public const TARGET_OWNER = 'owner';

    /** @return array<string, string> setting options: the owner, then each role */
    public function targets(): array
    {
        $targets = [self::TARGET_OWNER => __('tbe-billing::settings.options.purchase_alerts_owner')];

        if (class_exists(Role::class)) {
            foreach (Role::query()->orderBy('name')->get() as $role) {
                $targets[(string) $role->getKey()] = $role->name;
            }
        }

        return $targets;
    }

    public function send(Invoice $invoice): void
    {
        $text = $this->text($invoice);
        $recipients = $this->roleHolders($invoice);

        if ($recipients->isEmpty()) {
            $this->tellOwner($invoice, $text);

            return;
        }

        // notifyUser() contains each failure: one admin who blocked the bot
        // does not cost the others the alert.
        foreach ($recipients as $recipient) {
            notifyUser($recipient, $text, ['parse_mode' => 'HTML']);
        }
    }

    /** @return Collection<int, BotUser> */
    private function roleHolders(Invoice $invoice): Collection
    {
        $target = settings()->get('billing.purchase_alerts.target');

        if (! class_exists(Role::class) || ! is_numeric($target)) {
            return collect();
        }

        $role = Role::withoutGlobalScopes()
            ->where('bot_id', $invoice->getAttribute('bot_id'))
            ->find($target);

        return $role === null
            ? collect()
            : $role->users()->where('has_private_chat', true)->get();
    }

    private function tellOwner(Invoice $invoice, string $text): void
    {
        $bot = $invoice->bot;
        $peerId = $bot instanceof Bot ? $bot->getAttribute('bot_owner_peer_id') : null;

        if ($peerId === null) {
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
