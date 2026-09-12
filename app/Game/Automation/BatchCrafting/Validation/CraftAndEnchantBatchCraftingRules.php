<?php

namespace App\Game\Automation\BatchCrafting\Validation;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Enums\CraftAndEnchantBatchMode;
use App\Game\Automation\BatchCrafting\Enums\CraftSetPosition;
use App\Game\Automation\BatchCrafting\Requests\BatchCraftingRequest;
use App\Game\Automation\BatchCrafting\Validation\Concerns\ListingPriceRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CraftAndEnchantBatchCraftingRules
{
    use ListingPriceRules;

    /**
     * Build the Craft and Enchant batch type's validation rules for the requested mode.
     *
     * @param array $requestData The raw Batch Crafting request data.
     * @return array The Craft and Enchant validation rules.
     */
    public function rules(array $requestData): array
    {
        $mode = $requestData['progress']['craft_enchant_mode'] ?? null;

        return [
            'progress' => ['required', Rule::array($this->allowedProgressKeys($mode))],
            'progress.craft_enchant_mode' => ['required', new Enum(CraftAndEnchantBatchMode::class)],
            ...$this->modeSpecificRules($mode, $requestData),
        ];
    }

    /**
     * Return the client-owned progress keys allowed for the requested mode.
     *
     * @param string|null $mode The requested mode value.
     * @return array<int, string> The allowed client-owned progress keys.
     */
    private function allowedProgressKeys(?string $mode): array
    {
        return match ($mode) {
            CraftAndEnchantBatchMode::AMOUNT->value => ['craft_enchant_mode', 'specific_crafting_type', 'specific_item_id', 'prefix_id', 'suffix_id', 'craft_amount', 'output_destination', 'output_set_id', 'listing_price'],
            CraftAndEnchantBatchMode::EXPERIENCE->value => ['craft_enchant_mode', 'listing_price'],
            CraftAndEnchantBatchMode::SET->value => ['craft_enchant_mode', 'set_positions', 'enchantments', 'output_destination', 'output_set_id', 'listing_price'],
            default => ['craft_enchant_mode'],
        };
    }

    /**
     * Return the mode-specific validation rules for the requested mode.
     *
     * @param string|null $mode The requested mode value.
     * @param array $requestData The raw Batch Crafting request data.
     * @return array The mode-specific validation rules.
     */
    private function modeSpecificRules(?string $mode, array $requestData): array
    {
        return match ($mode) {
            CraftAndEnchantBatchMode::AMOUNT->value => $this->amountRules($requestData),
            CraftAndEnchantBatchMode::EXPERIENCE->value => $this->listingPriceRules(),
            CraftAndEnchantBatchMode::SET->value => $this->setRules($requestData),
            default => [],
        };
    }

    /**
     * Return the Craft and Enchant Amount specific validation rules.
     *
     * @param array $requestData The raw Batch Crafting request data.
     * @return array The Craft and Enchant Amount validation rules.
     */
    private function amountRules(array $requestData): array
    {
        return [
            'progress.specific_crafting_type' => ['required', 'string'],
            'progress.specific_item_id' => ['required', 'integer', 'min:1', $this->atLeastOneAffixRule($requestData)],
            'progress.prefix_id' => ['nullable', 'integer', 'min:1'],
            'progress.suffix_id' => ['nullable', 'integer', 'min:1'],
            'progress.craft_amount' => ['required', 'integer', 'min:1', 'max:'.BatchCraftingRequest::MAX_CRAFT_AMOUNT],
            ...$this->outputDestinationRules(CraftAndEnchantBatchMode::AMOUNT),
            ...$this->listingPriceRules(),
        ];
    }

    /**
     * Return a closure rule requiring that at least one affix id is present in the request.
     *
     * @param array $requestData The raw Batch Crafting request data.
     * @return \Closure The at-least-one-affix validation closure.
     */
    private function atLeastOneAffixRule(array $requestData): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($requestData): void {
            $progress = $requestData['progress'] ?? [];

            if (empty($progress['prefix_id']) && empty($progress['suffix_id'])) {
                $fail('At least one of a Prefix or a Suffix must be selected.');
            }
        };
    }

    /**
     * Return the Craft and Enchant Set specific validation rules.
     *
     * @param array $requestData The raw Batch Crafting request data.
     * @return array The Craft and Enchant Set validation rules.
     */
    private function setRules(array $requestData): array
    {
        $rules = [
            'progress.set_positions' => ['required', Rule::array($this->setPositionKeys())],
            'progress.enchantments' => ['required', Rule::array($this->setPositionKeys()), $this->setEnchantmentsRule($requestData)],
        ];

        foreach (CraftSetPosition::orderedCases() as $position) {
            $rules['progress.set_positions.'.$position->value] = $position->isRequired()
                ? ['required', 'integer', 'min:1']
                : ['nullable', 'integer', 'min:1'];

            $rules['progress.enchantments.'.$position->value] = ['nullable', 'array'];
            $rules['progress.enchantments.'.$position->value.'.prefix_id'] = ['nullable', 'integer', 'min:1'];
            $rules['progress.enchantments.'.$position->value.'.suffix_id'] = ['nullable', 'integer', 'min:1'];
        }

        return [...$rules, ...$this->outputDestinationRules(CraftAndEnchantBatchMode::SET), ...$this->listingPriceRules()];
    }

    /**
     * Return a closure rule requiring every included Set position to carry at least one affix.
     *
     * @param array $requestData The raw Batch Crafting request data.
     * @return \Closure The per-position affix requirement validation closure.
     */
    private function setEnchantmentsRule(array $requestData): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($requestData): void {
            $progress = $requestData['progress'] ?? [];
            $setPositions = $progress['set_positions'] ?? [];
            $enchantments = $progress['enchantments'] ?? [];

            foreach (CraftSetPosition::orderedCases() as $position) {
                if (empty($setPositions[$position->value])) {
                    continue;
                }

                $entry = $enchantments[$position->value] ?? null;

                if (empty($entry['prefix_id']) && empty($entry['suffix_id'])) {
                    $fail('Every included Set item must have at least one Prefix or Suffix selected.');

                    return;
                }
            }
        };
    }

    /**
     * Return every allowed Craft Set position key, in authoritative order.
     *
     * @return array<int, string> The allowed Craft Set position keys.
     */
    private function setPositionKeys(): array
    {
        return array_map(
            fn (CraftSetPosition $position): string => $position->value,
            CraftSetPosition::orderedCases(),
        );
    }

    /**
     * Return the shared output destination validation rules for a mode that supports retained output.
     *
     * @param CraftAndEnchantBatchMode $mode The requested mode.
     * @return array The output destination validation rules.
     */
    private function outputDestinationRules(CraftAndEnchantBatchMode $mode): array
    {
        $allowed = array_map(fn (BatchCraftingOutputDestination $destination): string => $destination->value, $mode->allowedOutputDestinations());

        return [
            'progress.output_destination' => [
                'required_if:disposition,'.BatchCraftingDisposition::KEEP->value,
                'prohibited_unless:disposition,'.BatchCraftingDisposition::KEEP->value,
                new Enum(BatchCraftingOutputDestination::class),
                Rule::in($allowed),
            ],
            'progress.output_set_id' => [
                'required_if:progress.output_destination,'.BatchCraftingOutputDestination::INVENTORY_SET->value,
                'prohibited_unless:progress.output_destination,'.BatchCraftingOutputDestination::INVENTORY_SET->value,
                'integer', 'min:1',
            ],
        ];
    }
}
