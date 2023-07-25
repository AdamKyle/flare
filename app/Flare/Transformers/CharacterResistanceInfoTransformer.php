<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;
use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;

class CharacterResistanceInfoTransformer extends BaseTransformer {

    /**
     * Gets the response data for the character sheet
     *
     * @param Character $character
     * @return array
     */
    public function transform(Character $character): array {

        $characterStatBuilder = resolve(CharacterStatBuilder::class)->setCharacter($character);

        return [
            'spell_evasion'          => $characterStatBuilder->reductionInfo()->getRingReduction('spell_evasion'),
            'affix_damage_reduction' => $characterStatBuilder->reductionInfo()->getRingReduction('affix_damage_reduction'),
        ];
    }
}
