<?php

namespace App\Game\Automation\BatchCrafting\Validation;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchMode;
use App\Game\Automation\BatchCrafting\Enums\CraftSetPosition;
use App\Game\Automation\BatchCrafting\Requests\BatchCraftingRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CraftBatchCraftingRules
{
    /**
     * Build the Craft batch type's validation rules for the requested craft mode.
     *
     * @param  array  $requestData  The raw Batch Crafting request data.
     * @return array The Craft validation rules.
     */
    public function rules(array $requestData): array
    {
        $mode = $requestData['progress']['craft_mode'] ?? null;

        return [
            'progress' => ['required', Rule::array($this->allowedProgressKeys($mode))],
            'progress.craft_mode' => ['required', new Enum(CraftingBatchMode::class)],
            ...$this->modeSpecificRules($mode),
        ];
    }

    /**
     * Return the client-owned progress keys allowed for the requested craft mode.
     *
     * @param  string|null  $mode  The requested craft mode value.
     * @return array<int, string> The allowed client-owned progress keys.
     */
    private function allowedProgressKeys(?string $mode): array
    {
        return match ($mode) {
            CraftingBatchMode::AMOUNT->value => ['craft_mode', 'specific_crafting_type', 'specific_item_id', 'craft_amount', 'output_destination'],
            CraftingBatchMode::SET->value => ['craft_mode', 'set_positions', 'output_destination', 'output_set_id'],
            default => ['craft_mode'],
        };
    }

    /**
     * Return the mode-specific validation rules for the requested craft mode.
     *
     * @param  string|null  $mode  The requested craft mode value.
     * @return array The mode-specific validation rules.
     */
    private function modeSpecificRules(?string $mode): array
    {
        return match ($mode) {
            CraftingBatchMode::AMOUNT->value => $this->amountRules(),
            CraftingBatchMode::SET->value => $this->setRules(),
            default => [],
        };
    }

    /**
     * Return the Craft Amount specific validation rules.
     *
     * @return array The Craft Amount validation rules.
     */
    private function amountRules(): array
    {
        return [
            'progress.specific_crafting_type' => ['required', 'string'],
            'progress.specific_item_id' => ['required', 'integer', 'min:1'],
            'progress.craft_amount' => ['required', 'integer', 'min:1', 'max:'.BatchCraftingRequest::MAX_CRAFT_AMOUNT],
            ...$this->outputDestinationRules(CraftingBatchMode::AMOUNT),
        ];
    }

    /**
     * Return the Craft Set specific validation rules.
     *
     * @return array The Craft Set validation rules.
     */
    private function setRules(): array
    {
        $rules = [
            'progress.set_positions' => ['required', Rule::array($this->setPositionKeys())],
        ];

        foreach (CraftSetPosition::orderedCases() as $position) {
            $rules['progress.set_positions.'.$position->value] = $position->isRequired()
                ? ['required', 'integer', 'min:1']
                : ['nullable', 'integer', 'min:1'];
        }

        return [...$rules, ...$this->outputDestinationRules(CraftingBatchMode::SET)];
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
     * @param  CraftingBatchMode  $mode  The requested craft mode.
     * @return array The output destination validation rules.
     */
    private function outputDestinationRules(CraftingBatchMode $mode): array
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
