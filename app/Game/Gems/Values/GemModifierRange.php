<?php

namespace App\Game\Gems\Values;

class GemModifierRange
{
    /**
     * @param CharacterGemModifierType $modifierType
     * @param int $minimum
     * @param int $maximum
     * @param bool $percentage
     */
    public function __construct(
        public readonly CharacterGemModifierType $modifierType,
        public readonly int $minimum,
        public readonly int $maximum,
        public readonly bool $percentage,
    ) {}

    /**
     * Serialize the modifier roll range into its API payload.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'modifier_type' => $this->modifierType->value,
            'minimum' => $this->minimum,
            'maximum' => $this->maximum,
            'percentage' => $this->percentage,
        ];
    }
}
