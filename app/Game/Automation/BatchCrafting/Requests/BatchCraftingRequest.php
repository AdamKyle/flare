<?php

namespace App\Game\Automation\BatchCrafting\Requests;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class BatchCraftingRequest extends FormRequest
{
    public const MAX_CRAFT_AMOUNT = 2000;

    /**
     * Determine whether the character is authorized to make this request.
     *
     * @return bool True for every request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return the validation rules for the Batch Crafting request.
     *
     * @return array The validation rules keyed by field name.
     */
    public function rules(): array
    {
        return [
            'batch_type' => ['required', new Enum(BatchCraftingType::class)],
            'disposition' => ['required', new Enum(BatchCraftingDisposition::class)],
            'progress' => ['required', 'array'],
            'progress.craft_mode' => ['required', new Enum(CraftingBatchMode::class)],
            'progress.specific_crafting_type' => ['required', 'string'],
            'progress.specific_item_id' => ['required', 'integer', 'min:1'],
            'progress.craft_amount' => ['required', 'integer', 'min:1', 'max:'.self::MAX_CRAFT_AMOUNT],
            'progress.output_destination' => [
                'required_if:disposition,'.BatchCraftingDisposition::KEEP->value,
                'prohibited_unless:disposition,'.BatchCraftingDisposition::KEEP->value,
                new Enum(BatchCraftingOutputDestination::class),
            ],
        ];
    }
}
