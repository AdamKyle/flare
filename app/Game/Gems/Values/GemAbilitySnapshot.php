<?php

namespace App\Game\Gems\Values;

use App\Flare\Models\GameGemAbility;

class GemAbilitySnapshot
{
    /**
     * @param int $id
     * @param string $name
     * @param string $description
     * @param string $abilityType
     * @param string $effectType
     * @param array|null $attackTypes
     * @param float|null $procChance
     * @param float $effectValue
     * @param string|null $scalingSource
     * @param bool $enabled
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
        public readonly string $abilityType,
        public readonly string $effectType,
        public readonly ?array $attackTypes,
        public readonly ?float $procChance,
        public readonly float $effectValue,
        public readonly ?string $scalingSource,
        public readonly bool $enabled,
    ) {}

    /**
     * Build the factual snapshot of a Gem Ability definition.
     *
     * @param GameGemAbility $gameGemAbility
     * @return GemAbilitySnapshot
     */
    public static function fromGameGemAbility(GameGemAbility $gameGemAbility): GemAbilitySnapshot
    {
        return new GemAbilitySnapshot(
            $gameGemAbility->id,
            $gameGemAbility->name,
            $gameGemAbility->description,
            $gameGemAbility->ability_type->value,
            $gameGemAbility->effect_type->value,
            $gameGemAbility->attack_types,
            $gameGemAbility->proc_chance,
            $gameGemAbility->effect_value,
            $gameGemAbility->scaling_source?->value,
            $gameGemAbility->enabled,
        );
    }

    /**
     * Serialize the Gem Ability snapshot into its API payload.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'ability_type' => $this->abilityType,
            'effect_type' => $this->effectType,
            'attack_types' => $this->attackTypes,
            'proc_chance' => $this->procChance,
            'effect_value' => $this->effectValue,
            'scaling_source' => $this->scalingSource,
            'enabled' => $this->enabled,
        ];
    }
}
