<?php

namespace TelegramBotEssentials\Billing\Telegram\Forms;

use Closure;
use Illuminate\Validation\ValidationException;
use TelegramBotEssentials\Billing\Services\OfferService;
use TelegramBotEssentials\Billing\Telegram\Features\Admin\OffersFeature;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Forms\Form;
use TelegramBotEssentials\Essence\Forms\Steps\Choice;
use TelegramBotEssentials\Essence\Forms\Steps\Text;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

/**
 * Creates an offer code. Nothing is written until the admin confirms the
 * summary, so an abandoned form leaves nothing behind.
 */
class CreateOfferForm extends Form
{
    protected string $type = 'OFFER_CREATE';

    protected int $perm = Roles::ADMIN->value;

    protected string $lang = 'tbe-billing::offers.wizard';

    public function steps(): array
    {
        $isPercentage = fn (array $answers) => ($answers['type'] ?? null) === 'percentage';

        return [
            Text::make('code')
                ->rules(['max:255', $this->codeIsFree(...)]),

            Choice::make('type')
                ->options([
                    'percentage' => __('tbe-billing::offers.wizard.chooseType.percentage'),
                    'fixed' => __('tbe-billing::offers.wizard.chooseType.fixed'),
                ]),

            Text::make('amount')
                ->dependsOn('type')
                ->prompt(fn (array $answers) => __('tbe-billing::offers.wizard.fields.amount.prompt.'.($answers['type'] ?? 'fixed')))
                ->rules(fn (array $answers) => [$this->amountIsValid($answers['type'] ?? null)])
                ->hint(__('tbe::forms.hints.numeric')),

            Text::make('max_discount')->skippable()->when($isPercentage)->rules($this->fieldRules('max_discount'))->hint(__('tbe::forms.hints.numeric')),
            Text::make('min_price')->skippable()->rules($this->fieldRules('min_price'))->hint(__('tbe::forms.hints.numeric')),
            Text::make('max_price')->skippable()->rules($this->fieldRules('max_price'))->hint(__('tbe::forms.hints.numeric')),
            Text::make('usage_limit')->skippable()->rules($this->fieldRules('usage_limit'))->hint(__('tbe::forms.hints.integer')),
            Text::make('usage_limit_per_user')->skippable()->rules($this->fieldRules('usage_limit_per_user'))->hint(__('tbe::forms.hints.integer')),
            Text::make('expires_at')->skippable()->rules($this->fieldRules('expires_at'))->hint(__('tbe::forms.hints.integer')),
        ];
    }

    public function onComplete(array $answers, array $ctx): TelegramResponse
    {
        $offer = app(OfferService::class)->createFromAnswers(wHook()->bot()->id, $answers);

        return OffersFeature::show($offer, $this->lastPage($ctx));
    }

    /** The list, refreshed so the new offer shows at the top of its first page. */
    public function originalScreen(array $ctx): ?TelegramResponse
    {
        return OffersFeature::menu(1);
    }

    /** @param  array<string, mixed>  $ctx */
    private function lastPage(array $ctx): int
    {
        return max(1, (int) ($ctx['lastPage'] ?? 1));
    }

    private function codeIsFree(string $attribute, mixed $value, Closure $fail): void
    {
        if (app(OfferService::class)->isCodeTaken(wHook()->bot()->id, (string) $value)) {
            $fail(__('tbe-billing::offers.wizard.errors.codeTaken'));
        }
    }

    /**
     * A rule that validates an optional field the way the offer service
     * does, so the form and the single-field edit share one set of limits and
     * messages. max_price is checked against the min price already answered.
     *
     * @return Closure(array<string, ?string>): array<mixed>
     */
    private function fieldRules(string $field): Closure
    {
        return fn (array $answers) => [
            function (string $attribute, mixed $value, Closure $fail) use ($field, $answers) {
                try {
                    app(OfferService::class)->parseField($field, (string) $value, $answers['min_price'] ?? null);
                } catch (ValidationException $e) {
                    $fail($e->getMessage());
                }
            },
        ];
    }

    private function amountIsValid(?string $type): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($type) {
            try {
                app(OfferService::class)->parseAmount((string) $value, $type);
            } catch (ValidationException $e) {
                $fail($e->getMessage());
            }
        };
    }
}
