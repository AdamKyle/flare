<?php

namespace App\Game\BatchCrafting\Requests;

use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;

class BatchCraftingPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $startableTypes = [
            BatchCraftingType::CRAFT->value,
            BatchCraftingType::CRAFT_AND_ENCHANT->value,
            BatchCraftingType::ENCHANT->value,
            BatchCraftingType::ALCHEMY->value,
            BatchCraftingType::HOLY_OILS->value,
            BatchCraftingType::TRINKETRY->value,
        ];

        return [
            'batch_type' => ['required', Rule::in($startableTypes)],
            'disposition' => ['nullable', Rule::in(array_column(BatchCraftingDisposition::cases(), 'value'))],
            'selected_items' => ['nullable', 'array'],
            'selected_items.*' => ['integer'],
            'selected_oils' => ['nullable', 'array'],
            'selected_oils.*' => ['integer'],
            'progress' => ['nullable', 'array'],
            'progress.craft_mode' => ['nullable', 'string', Rule::in(['specific_item', 'experience', 'event', 'craft_set', 'craft_enchant_set'])],
            'progress.specific_crafting_type' => ['nullable', 'string'],
            'progress.specific_item_id' => ['nullable', 'integer', 'min:1'],
            'progress.craft_amount' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'progress.enchant_affix_ids' => ['nullable', 'array', 'max:2'],
            'progress.enchant_affix_ids.*' => ['integer', 'min:1'],
            'progress.selected_set_id' => ['nullable', 'integer', 'min:1'],
            'progress.craft_enchant_set_mode' => ['nullable', 'string', Rule::in(['build_new'])],
            'progress.enchant_plan' => ['nullable', 'array'],
            'progress.enchant_plan.*.selected_item_id' => ['nullable', 'integer', 'min:1'],
            'progress.enchant_plan.*.prefix_affix_id' => ['nullable', 'integer', 'min:1'],
            'progress.enchant_plan.*.suffix_affix_id' => ['nullable', 'integer', 'min:1'],
            'progress.craft_set_plan' => ['nullable', 'array'],
            'progress.craft_set_plan.*.selected_item_id' => ['nullable', 'integer', 'min:1'],
            'progress.alchemy_mode' => ['nullable', 'string', Rule::in(['experience', 'amount'])],
            'progress.alchemy_item_id' => ['nullable', 'integer', 'min:1'],
            'progress.alchemy_amount' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'progress.holy_oil_mode' => ['nullable', 'string', Rule::in(['selected', 'set'])],
            'progress.output_destination' => ['nullable', 'string', Rule::in(['inventory', 'inventory_set', 'crafted_items_set'])],
            'progress.output_set_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->sometimes('progress.output_set_id', ['required'], function ($input) {
            return $this->isFiniteKeepOutputMode($input)
                && ($input->progress['output_destination'] ?? null) === 'inventory_set';
        });

        $validator->after(function ($validator) {
            $input = new Fluent($validator->getData());
            $outputDestination = $input->progress['output_destination'] ?? null;
            $outputSetId = $input->progress['output_set_id'] ?? null;
            $isFiniteKeepOutputMode = $this->isFiniteKeepOutputMode($input);

            if (! is_null($outputDestination) && ! $isFiniteKeepOutputMode) {
                $validator->errors()->add(
                    'progress.output_destination',
                    'Output destination is only available for finite Craft Amount, Craft Set, Craft and Enchant Amount, or Craft and Enchant Set batches using Keep.'
                );
            }

            if (! is_null($outputSetId) && ! ($isFiniteKeepOutputMode && $outputDestination === 'inventory_set')) {
                $validator->errors()->add(
                    'progress.output_set_id',
                    'An output set may only be selected for a finite Keep batch using Specified Empty Set.'
                );
            }
        });
    }

    /**
     * True only for the four finite KEEP modes that retain every final item and
     * offer the output destination selector: Craft Amount, Craft Set, Craft and
     * Enchant Amount, Craft and Enchant Set.
     */
    private function isFiniteKeepOutputMode(Fluent $input): bool
    {
        if ($input->disposition !== BatchCraftingDisposition::KEEP->value) {
            return false;
        }

        $mode = $input->progress['craft_mode'] ?? null;

        return ($input->batch_type === BatchCraftingType::CRAFT->value && in_array($mode, ['specific_item', 'craft_set'], true))
            || ($input->batch_type === BatchCraftingType::CRAFT_AND_ENCHANT->value && in_array($mode, ['specific_item', 'craft_enchant_set'], true));
    }
}
