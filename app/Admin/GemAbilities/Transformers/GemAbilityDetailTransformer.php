<?php

namespace App\Admin\GemAbilities\Transformers;

use App\Flare\Models\GameGemAbility;

class GemAbilityDetailTransformer
{
    /**
     * Transform a Gem Ability into its Admin detail representation.
     *
     * @param GameGemAbility $gameGemAbility
     * @return array
     */
    public function transform(GameGemAbility $gameGemAbility): array
    {
        return [
            'id' => $gameGemAbility->id,
            'name' => $gameGemAbility->name,
            'description' => $gameGemAbility->description,
            'ability_type' => $gameGemAbility->ability_type->value,
            'effect_type' => $gameGemAbility->effect_type->value,
            'attack_types' => $gameGemAbility->attack_types,
            'proc_chance' => $gameGemAbility->proc_chance,
            'effect_value' => $gameGemAbility->effect_value,
            'scaling_source' => $gameGemAbility->scaling_source?->value,
            'enabled' => $gameGemAbility->enabled,
        ];
    }
}
