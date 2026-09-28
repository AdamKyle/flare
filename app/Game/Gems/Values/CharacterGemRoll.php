<?php

namespace App\Game\Gems\Values;

class CharacterGemRoll
{
    /**
     * @param int $rollPosition
     * @param CharacterGemModifierType $modifierType
     * @param float|null $amount
     * @param int|null $gameGemAbilityId
     */
    private function __construct(
        public readonly int $rollPosition,
        public readonly CharacterGemModifierType $modifierType,
        public readonly ?float $amount,
        public readonly ?int $gameGemAbilityId,
    ) {}

    /**
     * Create a rolled modifier that carries a numeric amount.
     *
     * @param int $rollPosition
     * @param CharacterGemModifierType $modifierType
     * @param float $amount
     * @return CharacterGemRoll
     */
    public static function withAmount(int $rollPosition, CharacterGemModifierType $modifierType, float $amount): CharacterGemRoll
    {
        return new self($rollPosition, $modifierType, $amount, null);
    }

    /**
     * Create a rolled Gem Ability modifier that references an Admin-authored ability definition.
     *
     * @param int $rollPosition
     * @param int $gameGemAbilityId
     * @return CharacterGemRoll
     */
    public static function withAbility(int $rollPosition, int $gameGemAbilityId): CharacterGemRoll
    {
        return new self($rollPosition, CharacterGemModifierType::GEM_ABILITY, null, $gameGemAbilityId);
    }

    /**
     * Return the normalized identity of this roll used to detect identical Gems.
     *
     * @return string
     */
    public function signature(): string
    {
        return implode(':', [
            $this->rollPosition,
            $this->modifierType->value,
            is_null($this->amount) ? '' : number_format($this->amount, 8, '.', ''),
            $this->gameGemAbilityId ?? '',
        ]);
    }

    /**
     * Return the persisted character Gem modifier attributes for this roll.
     *
     * @return array
     */
    public function toModifierAttributes(): array
    {
        return [
            'roll_position' => $this->rollPosition,
            'modifier_type' => $this->modifierType,
            'amount' => $this->amount,
            'game_gem_ability_id' => $this->gameGemAbilityId,
        ];
    }
}
