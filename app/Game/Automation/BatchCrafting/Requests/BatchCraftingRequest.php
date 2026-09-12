<?php

namespace App\Game\Automation\BatchCrafting\Requests;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingModeService;
use App\Game\Automation\BatchCrafting\Validation\BatchCraftingRuleResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use ValueError;

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
     * Base rules validate the batch type itself; the remaining type-specific rules are
     * delegated to the resolved batch type's own validation rule builder.
     *
     * @return array The validation rules keyed by field name.
     */
    public function rules(): array
    {
        $type = BatchCraftingType::tryFrom($this->string('batch_type')->toString());

        $baseRules = [
            'batch_type' => ['required', new Enum(BatchCraftingType::class)],
            'disposition' => ['required', new Enum(BatchCraftingDisposition::class), Rule::in($this->allowedDispositionValues($type))],
        ];

        if (is_null($type)) {
            return $baseRules;
        }

        return [...$baseRules, ...resolve(BatchCraftingRuleResolver::class)->rules($type, $this->all())];
    }

    /**
     * Return the allowed backend disposition values for the requested batch type and mode.
     *
     * @param BatchCraftingType|null $type The requested Batch Crafting type.
     * @return array<int, string> The allowed disposition values.
     */
    private function allowedDispositionValues(?BatchCraftingType $type): array
    {
        $allValues = array_map(fn (BatchCraftingDisposition $disposition): string => $disposition->value, BatchCraftingDisposition::cases());

        if (is_null($type)) {
            return $allValues;
        }

        $modeValue = $this->input('progress.'.$type->progressModeKey());

        if (is_null($modeValue)) {
            return $allValues;
        }

        try {
            $dispositions = resolve(BatchCraftingModeService::class)->allowedDispositions($type, $modeValue);
        } catch (ValueError) {
            return $allValues;
        }

        return array_map(fn (BatchCraftingDisposition $disposition): string => $disposition->value, $dispositions);
    }
}
