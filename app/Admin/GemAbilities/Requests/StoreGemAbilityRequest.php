<?php

namespace App\Admin\GemAbilities\Requests;

use App\Game\Core\Combat\Values\AttackType;
use App\Game\Gems\Values\GemAbilityEffectType;
use App\Game\Gems\Values\GemAbilityScalingSource;
use App\Game\Gems\Values\GemAbilityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGemAbilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for a Gem Ability definition, contextual to its active or passive type.
     *
     * @return array
     */
    public function rules(): array
    {
        $isActive = $this->input('ability_type') === GemAbilityType::ACTIVE->value;

        $allowedEffectTypes = $isActive ? GemAbilityEffectType::activeEffects() : GemAbilityEffectType::passiveEffects();

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('game_gem_abilities', 'name')->ignore($this->route('gameGemAbility'))],
            'description' => 'required|string',
            'ability_type' => ['required', Rule::enum(GemAbilityType::class)],
            'effect_type' => ['required', Rule::enum(GemAbilityEffectType::class)->only($allowedEffectTypes)],
            'attack_types' => 'required|array|min:1',
            'attack_types.*' => ['required', 'string', 'distinct', Rule::enum(AttackType::class)->only(AttackType::baseAttackTypes())],
            'proc_chance' => $isActive ? 'required|numeric|min:0.01|max:1' : 'prohibited',
            'effect_value' => 'required|numeric|min:0.01|max:1',
            'scaling_source' => $isActive ? ['required', Rule::enum(GemAbilityScalingSource::class)] : 'prohibited',
            'enabled' => 'required|boolean',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a Gem Ability name.',
            'name.unique' => 'A Gem Ability with this name already exists.',
            'description.required' => 'Enter a Gem Ability description.',
            'ability_type.required' => 'Select whether the Gem Ability is active or passive.',
            'effect_type.required' => 'Select the Gem Ability effect.',
            'effect_type.enum' => 'Active abilities must deal bonus damage; passive abilities must use a weapon damage, spell damage, healing or defence modifier.',
            'attack_types.required' => 'Select at least one attack action.',
            'attack_types.min' => 'Select at least one attack action.',
            'attack_types.*.enum' => 'Attack actions must be Attack, Cast, Attack and Cast, Cast and Attack, or Defend.',
            'proc_chance.required' => 'Enter a proc chance for an active Gem Ability.',
            'proc_chance.prohibited' => 'Passive Gem Abilities do not use a proc chance.',
            'effect_value.required' => 'Enter the Gem Ability effect value.',
            'scaling_source.required' => 'Select the scaling source for an active Gem Ability.',
            'scaling_source.prohibited' => 'Passive Gem Abilities do not use a scaling source.',
        ];
    }
}
