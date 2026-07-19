<?php

namespace App\Game\BatchCrafting\Requests;

use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;

class BatchCraftingStartRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $progress = $this->input('progress', []);
        $batchType = $this->input('batch_type');

        if (in_array($batchType, [BatchCraftingType::CRAFT->value, BatchCraftingType::CRAFT_AND_ENCHANT->value], true)) {
            $progress['craft_mode'] = $progress['craft_mode'] ?? 'experience';
        }

        if ($batchType === BatchCraftingType::ENCHANT->value) {
            $progress['enchant_mode'] = $progress['enchant_mode'] ?? 'event';
        }

        if ($batchType === BatchCraftingType::ALCHEMY->value) {
            $progress['alchemy_mode'] = $progress['alchemy_mode'] ?? 'experience';
        }

        if ($batchType === BatchCraftingType::TRINKETRY->value) {
            $progress['trinketry_mode'] = $progress['trinketry_mode'] ?? 'experience';
        }

        if ($batchType === BatchCraftingType::HOLY_OILS->value) {
            $progress['holy_oil_mode'] = $progress['holy_oil_mode'] ?? 'selected';
        }

        $this->merge(['progress' => $progress]);
    }

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
            'disposition' => ['required', Rule::in(array_column(BatchCraftingDisposition::cases(), 'value'))],
            'selected_items' => ['nullable', 'array'],
            'selected_oils' => ['nullable', 'array'],
            'listing_price' => ['nullable', 'integer', 'min:1'],
            'progress' => ['nullable', 'array'],
            'progress.craft_mode' => ['nullable', 'string', Rule::in(['specific_item', 'experience', 'event', 'craft_set', 'craft_enchant_set'])],
            'progress.craft_experience_skill' => ['nullable', 'string', Rule::in(['weapon', 'armour', 'ring', 'spell', 'enchanting'])],
            'progress.specific_crafting_type' => ['nullable', 'string'],
            'progress.specific_item_type' => ['nullable', 'string'],
            'progress.specific_item_id' => ['nullable', 'integer', 'min:1'],
            'progress.craft_amount' => ['nullable', 'integer', 'min:1'],
            'progress.enchant_affix_ids' => ['nullable', 'array', 'min:1', 'max:2'],
            'progress.enchant_affix_ids.*' => ['integer', 'min:1'],
            'progress.enchant_mode' => ['nullable', 'string', Rule::in(['event'])],
            'progress.selected_set_id' => ['nullable', 'integer', 'min:1'],
            'progress.craft_enchant_set_mode' => ['nullable', 'string', Rule::in(['build_new'])],
            'progress.enchant_plan' => ['nullable', 'array'],
            'progress.craft_set_plan' => ['nullable', 'array'],
            'progress.output_destination' => ['nullable', 'string', Rule::in(['inventory', 'inventory_set', 'crafted_items_set'])],
            'progress.output_set_id' => ['nullable', 'integer', 'min:1'],
            'progress.alchemy_mode' => ['nullable', 'string', Rule::in(['experience', 'amount'])],
            'progress.alchemy_item_id' => ['nullable', 'integer', 'min:1'],
            'progress.alchemy_amount' => ['nullable', 'integer', 'min:1'],
            'progress.trinketry_mode' => ['nullable', 'string', Rule::in(['experience'])],
            'progress.holy_oil_mode' => ['nullable', 'string', Rule::in(['selected', 'set'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->sometimes('selected_items', ['required', 'array', 'min:1'], function ($input) {
            return $input->batch_type === BatchCraftingType::HOLY_OILS->value
                && ($input->progress['holy_oil_mode'] ?? 'selected') === 'selected';
        });

        $validator->sometimes('selected_oils', ['required', 'array', 'min:1'], function ($input) {
            return $input->batch_type === BatchCraftingType::HOLY_OILS->value;
        });

        $validator->sometimes('progress.selected_set_id', ['required'], function ($input) {
            if ($input->batch_type === BatchCraftingType::HOLY_OILS->value) {
                return ($input->progress['holy_oil_mode'] ?? 'selected') === 'set';
            }

            return false;
        });

        $validator->sometimes('progress.craft_mode', ['required'], function ($input) {
            return in_array($input->batch_type, [
                BatchCraftingType::CRAFT->value,
                BatchCraftingType::CRAFT_AND_ENCHANT->value,
            ], true);
        });

        $validator->sometimes('progress.craft_mode', [Rule::notIn(['event', 'craft_set'])], function ($input) {
            return $input->batch_type === BatchCraftingType::CRAFT_AND_ENCHANT->value;
        });

        $validator->sometimes('progress.specific_crafting_type', ['required'], function ($input) {
            return in_array($input->batch_type, [
                BatchCraftingType::CRAFT->value,
                BatchCraftingType::CRAFT_AND_ENCHANT->value,
            ], true) && ($input->progress['craft_mode'] ?? null) === 'specific_item';
        });

        $validator->sometimes('progress.specific_item_id', ['required'], function ($input) {
            return in_array($input->batch_type, [
                BatchCraftingType::CRAFT->value,
                BatchCraftingType::CRAFT_AND_ENCHANT->value,
            ], true) && ($input->progress['craft_mode'] ?? null) === 'specific_item';
        });

        $validator->sometimes('progress.craft_amount', ['required'], function ($input) {
            return in_array($input->batch_type, [
                BatchCraftingType::CRAFT->value,
                BatchCraftingType::CRAFT_AND_ENCHANT->value,
            ], true) && ($input->progress['craft_mode'] ?? null) === 'specific_item';
        });

        $validator->sometimes('progress.enchant_affix_ids', ['required'], function ($input) {
            return $input->batch_type === BatchCraftingType::CRAFT_AND_ENCHANT->value
                && ($input->progress['craft_mode'] ?? null) === 'specific_item';
        });

        $validator->sometimes('progress.alchemy_mode', ['required'], function ($input) {
            return $input->batch_type === BatchCraftingType::ALCHEMY->value;
        });

        $validator->sometimes('progress.alchemy_amount', ['required'], function ($input) {
            return $input->batch_type === BatchCraftingType::ALCHEMY->value
                && ($input->progress['alchemy_mode'] ?? null) === 'amount';
        });

        $validator->sometimes('progress.alchemy_item_id', ['required'], function ($input) {
            return $input->batch_type === BatchCraftingType::ALCHEMY->value
                && ($input->progress['alchemy_mode'] ?? null) === 'amount';
        });

        $validator->sometimes('listing_price', ['required', 'integer', 'min:1'], function ($input) {
            return $input->disposition === BatchCraftingDisposition::LIST->value;
        });

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
     * True only for the four finite KEEP modes that retain every final item and offer
     * the output destination selector: Craft Amount, Craft Set, Craft and Enchant
     * Amount, Craft and Enchant Set.
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
