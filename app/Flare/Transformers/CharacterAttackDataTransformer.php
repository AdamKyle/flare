<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;
use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use App\Flare\Transformers\DataSets\CharacterAttackData;

class CharacterAttackDataTransformer extends BaseTransformer {

    private bool $ignoreReductions = false;

    public function setIgnoreReductions(bool $ignoreReductions): void {
        $this->ignoreReductions = $ignoreReductions;
    }

    /**
     * Gets the response data for the character sheet
     *
     * @param Character $character
     * @return array
     */
    public function transform(Character $character): array {
        $characterStatBuilder = resolve(CharacterStatBuilder::class)->setCharacter($character, $this->ignoreReductions);

        $characterAttackData = resolve(CharacterAttackData::class);

        $characterAttackData->setIncludeReductions($this->ignoreReductions);

        return $characterAttackData->attackData($character, $characterStatBuilder);
    }
}
