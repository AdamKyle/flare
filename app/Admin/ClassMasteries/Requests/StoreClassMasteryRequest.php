<?php

namespace App\Admin\ClassMasteries\Requests;

use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\Core\Combat\Values\AttackType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassMasteryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'game_class_id' => ['required', 'integer', Rule::exists('game_classes', 'id')],
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'requires_class_rank_level' => ['required', 'integer', 'min:0', 'max:'.ClassRankValue::MAX_LEVEL],
            'specialty_damage' => 'nullable|integer',
            'increase_specialty_damage_per_level' => 'nullable|integer',
            'specialty_damage_uses_damage_stat_amount' => 'nullable|numeric',
            'attack_type_required' => ['nullable', 'string', Rule::in($this->allowedAttackTypeValues())],
            'base_damage_mod' => 'nullable|numeric',
            'base_ac_mod' => 'nullable|numeric',
            'base_healing_mod' => 'nullable|numeric',
            'base_spell_damage_mod' => 'nullable|numeric',
            'health_mod' => 'nullable|numeric',
            'base_damage_stat_increase' => 'nullable|numeric',
            'spell_evasion' => 'nullable|numeric',
            'affix_damage_reduction' => 'nullable|numeric',
            'healing_reduction' => 'nullable|numeric',
            'skill_reduction' => 'nullable|numeric',
            'resistance_reduction' => 'nullable|numeric',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'game_class_id.required' => 'Select the owning Class.',
            'name.required' => 'Enter a Class Mastery name.',
            'requires_class_rank_level.required' => 'Enter the required Class Rank level.',
        ];
    }

    /**
     * Return every valid `attack_type_required` value: the existing AttackType values plus `any`.
     */
    private function allowedAttackTypeValues(): array
    {
        $values = array_map(fn (AttackType $attackType): string => $attackType->value, AttackType::cases());
        $values[] = 'any';

        return $values;
    }
}
