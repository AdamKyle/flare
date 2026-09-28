<?php

namespace App\Game\Gems\Transformers;

use App\Flare\Models\CharacterGemModifier;
use App\Flare\Models\GameGemAbility;
use App\Flare\Models\Gem;
use League\Fractal\TransformerAbstract;

class CharacterGemTransformer extends TransformerAbstract
{
    /**
     * Transform a character-domain Gem into its generic three-modifier representation.
     *
     * @param Gem $gem
     * @return array
     */
    public function transform(Gem $gem): array
    {
        $gem->loadMissing('characterModifiers.gameGemAbility');

        return [
            'id' => $gem->id,
            'name' => $gem->name,
            'tier' => $gem->tier,
            'domain' => $gem->domain,
            'modifiers' => $gem->characterModifiers
                ->map(fn (CharacterGemModifier $modifier): array => $this->transformModifier($modifier))
                ->values()
                ->all(),
        ];
    }

    /**
     * Transform one rolled modifier into its factual representation.
     *
     * @param CharacterGemModifier $modifier
     * @return array
     */
    public function transformModifier(CharacterGemModifier $modifier): array
    {
        return [
            'roll_position' => $modifier->roll_position,
            'modifier_type' => $modifier->modifier_type->value,
            'amount' => $modifier->amount,
            'ability' => is_null($modifier->gameGemAbility) ? null : $this->transformAbility($modifier->gameGemAbility),
        ];
    }

    /**
     * Transform a Gem Ability definition into its factual representation.
     *
     * @param GameGemAbility $gameGemAbility
     * @return array
     */
    public function transformAbility(GameGemAbility $gameGemAbility): array
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
