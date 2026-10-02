<?php

namespace App\Game\Gems\Values;

use App\Flare\Models\CharacterGemModifier;

class CharacterGemModifierSnapshot
{
    /**
     * @param int $rollPosition
     * @param string $modifierType
     * @param float|null $amount
     * @param GemAbilitySnapshot|null $ability
     */
    public function __construct(
        public readonly int $rollPosition,
        public readonly string $modifierType,
        public readonly ?float $amount,
        public readonly ?GemAbilitySnapshot $ability,
    ) {}

    /**
     * Build the factual snapshot of one rolled character Gem modifier.
     *
     * @param CharacterGemModifier $modifier
     * @return CharacterGemModifierSnapshot
     */
    public static function fromModifier(CharacterGemModifier $modifier): CharacterGemModifierSnapshot
    {
        return new CharacterGemModifierSnapshot(
            $modifier->roll_position,
            $modifier->modifier_type->value,
            $modifier->amount,
            is_null($modifier->gameGemAbility) ? null : GemAbilitySnapshot::fromGameGemAbility($modifier->gameGemAbility),
        );
    }

    /**
     * Serialize the modifier snapshot into its API payload.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'roll_position' => $this->rollPosition,
            'modifier_type' => $this->modifierType,
            'amount' => $this->amount,
            'ability' => $this->ability?->toArray(),
        ];
    }
}
