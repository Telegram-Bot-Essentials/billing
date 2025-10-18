<?php

namespace TelegramBotEssentials\Billing\Telegram\Features\Member;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Billing\DTOs\Gateway;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

class InvoiceFeature
{
    static string $type = 'INVOICE';

    public static function invoice(Invoice $invoice, ?string $encodedCallback = null): TelegramResponse
    {
        $text = __('tbe-billing::invoice.summary.text.information', [
            'invoiceId' => $invoice->id,
            'orderDescription' => $invoice->payable->description ?? null
        ]);

        $replyMarkup = Keyboard::make()->inline();

        gateways()->getGateways()->each(function (Gateway $gateway) use ($invoice, $replyMarkup) {
            $keyboard = $gateway->getInlineKeyboard($invoice);
            if($keyboard){
                $replyMarkup->row([$keyboard]);
            }
        });

//        if(wHook()->bot()->settings->pay_with_card) {
//            $replyMarkup->row([Keyboard::inlineButton([
//                'text' => __('tbe-billing::invoice.summary.keys.to_card', [
//                    'price' => number_format(priceIn($invoice->price)->toIRT())
//                ]),
//                'callback_data' => encodeCallback(self::$type, ['to_card', $invoice->id])
//            ])]);
//        }
//
//        if(wHook()->bot()->settings->zirgozar){
//            $replyMarkup->row([Keyboard::inlineButton([
//                'text' => __('tbe-billing::invoice.summary.keys.to_zirgozar', [
//                    'price' => number_format(priceIn($invoice->price)->toIRT())
//                ]),
//                'url' => route('invoice.zirgozar.pay', ['token' => $invoice->public_token])
//            ])]);
//        }
//
//        if(wHook()->bot()->settings->zibal){
//            $replyMarkup->row([Keyboard::inlineButton([
//                'text' => __('tbe-billing::invoice.summary.keys.to_zibal', [
//                    'price' => number_format(priceIn($invoice->price)->toIRT())
//                ]),
//                'url' => route('invoice.zibal.pay', ['token' => $invoice->public_token])
//            ])]);
//        }
//
//        if(wHook()->bot()->settings->zarinpal){
//            $replyMarkup->row([Keyboard::inlineButton([
//                'text' => __('tbe-billing::invoice.summary.keys.to_zarinpal', [
//                    'price' => number_format(priceIn($invoice->price)->toIRT())
//                ]),
//                'url' => route('invoice.zarinpal.pay', ['token' => $invoice->public_token])
//            ])]);
//        }
//
//        if(!($invoice->payable instanceof CreditOrder) && wHook()->bot()->settings->wallet){
//            $replyMarkup->row([Keyboard::inlineButton([
//                'text' => __('tbe-billing::invoice.summary.keys.by_wallet', [
//                    'price' => currency()->priceFormat($invoice->price)
//                ]),
//                'callback_data' => encodeCallback(self::$type, ['by_wallet', $invoice->id])
//            ])]);
//        }

        $noPaymentMethods = empty($replyMarkup->all());

        if($encodedCallback){
            $replyMarkup->row([Keyboard::inlineButton([
                'text' => __('tbe-billing::invoice.summary.keys.back_to_previous'),
                'callback_data' => $encodedCallback
            ])]);
        }

        if($noPaymentMethods){
            return (new TelegramResponse(
                text: __('tbe-billing::invoice.summary.text.noPaymentMethods', [
                    'invoiceId' => $invoice->id,
                    'orderDescription' => $invoice->payable->description ?? null
                ]),
                replyMarkup: empty($replyMarkup->all()) ? null : $replyMarkup,
                answer: __('tbe-billing::invoice.summary.answers.noPaymentMethods')
            ))->messageMetaModel($invoice, 'invoice_view');
        }

        return (new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            answer: $invoice->wasRecentlyCreated ?
                __('tbe-billing::invoice.summary.answers.created') :
                __('tbe-billing::invoice.summary.answers.main')
        ))->messageMetaModel($invoice, 'invoice_view');
    }
}
