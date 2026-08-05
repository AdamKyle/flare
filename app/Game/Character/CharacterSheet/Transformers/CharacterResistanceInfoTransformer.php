<?php

namespace App\Game\Character\CharacterSheet\Transformers;

use App\Flare\Models\Character;
use App\Flare\Transformers\BaseTransformer;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;

class CharacterResistanceInfoTransformer extends BaseTransformer
{
    public function __construct(
        private readonly CharacterStatBuilder $characterStatBuilder,
    ) {}

    /**
     * Gets the response data for the character sheet
     */
    public function transform(Character $character): array
    {

        $characterStatBuilder = $this->characterStatBuilder->setCharacter($character);

        return [
            'spell_evasion' => $characterStatBuilder->reductionInfo()->getRingReduction('spell_evasion'),
            'affix_damage_reduction' => $characterStatBuilder->reductionInfo()->getRingReduction('affix_damage_reduction'),
            'healing_reduction' => $characterStatBuilder->reductionInfo()->getRingReduction('healing_reduction'),
        ];
    }
}
