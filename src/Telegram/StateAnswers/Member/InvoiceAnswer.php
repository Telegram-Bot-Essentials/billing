<?php

namespace TelegramBotEssentials\Billing\Telegram\StateAnswers\Member;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Billing\Models\Attempts\ToCardAttempt;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Enums\AllowableFields;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;

class InvoiceAnswer extends StateAnswer
{
    protected string $type = 'INVOICE';
    protected int $perm = Roles::MEMBER->value;
    protected array $allowedFields = [AllowableFields::TEXT->value, AllowableFields::PHOTO->value];

//    /**
//     * @param string $method
//     * @throws BindingResolutionException
//     * @throws TelegramSDKException
//     * @throws LogicException
//     */
//    public function handle(string $method): void
//    {
//        switch (strtolower($method)) {
//            case "pay_to_card":
//                $this->payToCard();
//                break;
//        }
//    }
//
//    /**
//     * @throws BindingResolutionException
//     * @throws TelegramSDKException
//     * @throws LogicException
//     */
//    private function payToCard(): void
//    {
//        $invoice = Invoice::findOrFail($this->params['invoice_id']);
//        if(!$invoice->paymentAttempt instanceof ToCardAttempt) return;
//
//        $this->storePaymentInformation($invoice);
//
//        $invoice->paymentAttempt->received_at = now();
//        $invoice->paymentAttempt->save();
//
//        $invoice->messageMeta->lockAction(__('tbe-billing::invoice.to_card.lock-keys.user-wait_for_payment_processing'));
//        wHook()->user()->changeState();
//        wHook()->api()->sendMessage([
//            'chat_id' => wHook()->update()->message->from->id,
//            'text' => __('tbe-billing::invoice.to_card.text.user-payment_result'),
//            'reply_markup' => wHook()->user()->getKeyboard(),
//        ]);
//
//        $text = __('tbe-billing::invoice.to_card.text.admin-payment_result', [
//            'invoiceId' => $invoice->id,
//            'invoiceDescription' => $invoice->payable->description ?? null,
//            'paymentDescription' => wHook()->update()->message?->photo ? wHook()->update()->message->caption : wHook()->update()->message->text,
//        ]);
//
//        $replyMarkup = Keyboard::make()->inline();
//
//        $replyMarkup->row([Keyboard::inlineButton([
//            'text' => __('tbe-billing::invoice.to_card.keys.admin-accept_payment'),
//            'callback_data' => encodeCallback('MANAGE_INVOICE', ['accept_card_payment', $invoice->paymentAttempt->id])
//        ]), Keyboard::inlineButton([
//            'text' => __('tbe-billing::invoice.to_card.keys.admin-reject_payment'),
//            'callback_data' => encodeCallback('MANAGE_INVOICE', ['reject_card_payment', $invoice->paymentAttempt->id])
//        ])]);
//
//
//        if (wHook()->update()->message?->text) {
//            $message = wHook()->api()->sendMessage([
//                'chat_id' => wHook()->bot()->settings->transactions_chat_id,
//                'text' => $text,
//                'reply_markup' => $replyMarkup,
//            ]);
//        } else {
//            $message = wHook()->api()->copyMessage([
//                'chat_id' => wHook()->bot()->settings->transactions_chat_id,
//                'from_chat_id' => wHook()->update()->message->from->id,
//                'message_id' => wHook()->update()->message->messageId,
//                'caption' => $text,
//                'reply_markup' => $replyMarkup,
//            ]);
//        }
//
//        $invoice->paymentAttempt->messageMeta
//            ->initializeModel(wHook()->bot()->settings->transactions_chat_id, $message->messageId, $message->text, $message->replyMarkup);
//    }
//
//    /**
//     * @throws TelegramSDKException
//     */
//    private function storePaymentInformation(Invoice $invoice): void
//    {
//        $toCardAttempt = $invoice->paymentAttempt;
//        $toCardAttempt->info_text = wHook()->update()->message?->photo ? wHook()->update()->message->caption : wHook()->update()->message->text;
//
//        if (wHook()->update()->message?->photo[0] ?? null) {
//            $file = wHook()->api()->getFile(['file_id' => wHook()->update()->message->photo[0]->file_id]);
//            $path = Storage::disk()->path(time() . '.jpg');
//            wHook()->api()->downloadFile($file, $path);
//            $base64EncodedPhoto = base64_encode(file_get_contents($path));
//            Storage::delete($path);
//            $toCardAttempt->info_photo = $base64EncodedPhoto;
//        }
//
//        $toCardAttempt->save();
//    }
//
//    /**
//     * @throws TelegramSDKException
//     */
//    function cancel(): void
//    {
//        $invoice = Invoice::findOrFail($this->params['invoice_id']);
//        $invoice->messageMeta->continueAction();
//    }
}
