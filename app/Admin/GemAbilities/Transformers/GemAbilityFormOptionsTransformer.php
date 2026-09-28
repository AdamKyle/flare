<?php

namespace App\Admin\GemAbilities\Transformers;

use App\Game\Core\Combat\Values\AttackType;
use App\Game\Gems\Values\GemAbilityEffectType;
use App\Game\Gems\Values\GemAbilityScalingSource;
use App\Game\Gems\Values\GemAbilityType;

class GemAbilityFormOptionsTransformer
{
    /**
     * Build the Admin Gem Ability form option values from the owning Gem and combat enums.
     *
     * @return array
     */
    public function transform(): array
    {
        return [
            'ability_types' => array_map(fn (GemAbilityType $abilityType): string => $abilityType->value, GemAbilityType::cases()),
            'active_effect_types' => array_map(fn (GemAbilityEffectType $effectType): string => $effectType->value, GemAbilityEffectType::activeEffects()),
            'passive_effect_types' => array_map(fn (GemAbilityEffectType $effectType): string => $effectType->value, GemAbilityEffectType::passiveEffects()),
            'attack_types' => array_map(fn (AttackType $attackType): string => $attackType->value, AttackType::baseAttackTypes()),
            'scaling_sources' => array_map(fn (GemAbilityScalingSource $scalingSource): string => $scalingSource->value, GemAbilityScalingSource::cases()),
        ];
    }
}
