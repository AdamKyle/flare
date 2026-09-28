<?php

namespace App\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Game\Core\Combat\Values\ElementAttackData;
use App\Game\Gems\Contracts\CharacterGemEffects;

class ElementalAtonement extends BaseAttribute
{
    /**
     * @param CharacterGemEffects $characterGemEffects
     * @param ElementAttackData $elementAttackData
     */
    public function __construct(
        private readonly CharacterGemEffects $characterGemEffects,
        private readonly ElementAttackData $elementAttackData,
    ) {}

    /**
     * Resolve the summed and capped equipped character-Gem atonements and dominant element.
     *
     * @return array|null
     */
    public function calculateAtonement(): ?array
    {
        $effects = $this->characterGemEffects->resolveForCharacterId($this->character->id);
        $atonements = [
            'Fire' => $effects->fireAtonement(),
            'Water' => $effects->waterAtonement(),
            'Ice' => $effects->iceAtonement(),
        ];
        $highestElementDamage = $this->elementAttackData->getHighestElementDamage($atonements);

        return [
            'atonements' => $atonements,
            'highest_element' => [
                'name' => $highestElementDamage <= 0
                    ? 'N/A'
                    : $this->elementAttackData->getHighestElementName($atonements, $highestElementDamage),
                'damage' => $highestElementDamage,
            ],
        ];
    }
}
