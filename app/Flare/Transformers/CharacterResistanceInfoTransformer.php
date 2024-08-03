<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;

class CharacterResistanceInfoTransformer extends BaseTransformer
{
    /**
     * Gets the response data for the character sheet
     */
    public function transform(Character $character): array
    {

        $characterStatBuilder = resolve(CharacterStatBuilder::class)->setCharacter($character);

        return [
            'spell_evasion' => $characterStatBuilder->reductionInfo()->getRingReduction('spell_evasion'),
            'affix_damage_reduction' => $characterStatBuilder->reductionInfo()->getRingReduction('affix_damage_reduction'),
            'healing_reduction' => $characterStatBuilder->reductionInfo()->getRingReduction('healing_reduction'),
        ];
    }
}
