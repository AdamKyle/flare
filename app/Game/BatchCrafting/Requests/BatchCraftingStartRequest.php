<?php

namespace App\Game\BatchCrafting\Requests;

use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchCraftingStartRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $progress = $this->input('progress', []);
        $batchType = $this->input('batch_type');

        if (in_array($batchType, [BatchCraftingType::CRAFT->value, BatchCraftingType::CRAFT_AND_ENCHANT->value], true)) {
            $progress['craft_mode'] = $progress['craft_mode'] ?? 'full_set';
            $progress['set_count'] = $progress['set_count'] ?? ($batchType === BatchCraftingType::CRAFT_AND_ENCHANT->value ? 5 : 1);
        }

        if ($batchType === BatchCraftingType::ALCHEMY->value) {
            $progress['alchemy_mode'] = $progress['alchemy_mode'] ?? 'experience';
        }

        if ($batchType === BatchCraftingType::TRINKETRY->value) {
            $progress['trinketry_mode'] = $progress['trinketry_mode'] ?? 'amount';
            $progress['trinketry_amount'] = $progress['trinketry_amount'] ?? 1;
        }

        $this->merge(['progress' => $progress]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_type' => ['required', Rule::in(array_column(BatchCraftingType::cases(), 'value'))],
            'disposition' => ['required', Rule::in(array_column(BatchCraftingDisposition::cases(), 'value'))],
            'selected_items' => ['nullable', 'array'],
            'selected_oils' => ['nullable', 'array'],
            'progress' => ['nullable', 'array'],
            'progress.craft_mode' => ['nullable', 'string', Rule::in(['full_set', 'specific_item', 'experience'])],
            'progress.specific_crafting_type' => ['nullable', 'string'],
            'progress.specific_item_type' => ['nullable', 'string'],
            'progress.specific_item_id' => ['nullable', 'integer', 'min:1'],
            'progress.craft_amount' => ['nullable', 'integer', 'min:1'],
            'progress.set_count' => ['nullable', 'integer', 'min:1'],
            'progress.enchant_affix_ids' => ['nullable', 'array', 'min:1', 'max:2'],
            'progress.enchant_affix_ids.*' => ['integer', 'min:1'],
            'progress.alchemy_mode' => ['nullable', 'string', Rule::in(['experience', 'amount'])],
            'progress.alchemy_amount' => ['nullable', 'integer', 'min:1'],
            'progress.trinketry_mode' => ['nullable', 'string', Rule::in(['experience', 'amount'])],
            'progress.trinketry_amount' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->sometimes('selected_items', ['required', 'array', 'min:1'], function ($input) {
            return $input->batch_type === BatchCraftingType::HOLY_OILS->value;
        });

        $validator->sometimes('selected_oils', ['required', 'array', 'min:1'], function ($input) {
            return $input->batch_type === BatchCraftingType::HOLY_OILS->value;
        });

        $validator->sometimes('progress.craft_mode', ['required'], function ($input) {
            return in_array($input->batch_type, [
                BatchCraftingType::CRAFT->value,
                BatchCraftingType::CRAFT_AND_ENCHANT->value,
            ], true);
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

        $validator->sometimes('progress.trinketry_amount', ['required'], function ($input) {
            return $input->batch_type === BatchCraftingType::TRINKETRY->value
                && ($input->progress['trinketry_mode'] ?? 'amount') === 'amount';
        });
    }
}
