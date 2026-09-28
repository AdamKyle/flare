<?php

namespace App\Admin\GemAbilities\Transformers;

use App\Flare\Models\GameGemAbility;
use League\Fractal\TransformerAbstract;

class GemAbilityListTransformer extends TransformerAbstract
{
    /**
     * Transform a Gem Ability into its Admin list-row representation.
     *
     * @param GameGemAbility $gameGemAbility
     * @return array
     */
    public function transform(GameGemAbility $gameGemAbility): array
    {
        return [
            'id' => $gameGemAbility->id,
            'name' => $gameGemAbility->name,
            'ability_type' => $gameGemAbility->ability_type->value,
            'effect_type' => $gameGemAbility->effect_type->value,
            'enabled' => $gameGemAbility->enabled,
        ];
    }
}
