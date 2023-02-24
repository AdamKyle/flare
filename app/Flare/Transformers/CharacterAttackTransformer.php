<?php

namespace App\Flare\Transformers;

use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use Facades\App\Flare\Transformers\DataSets\CharacterAttackData;
use App\Flare\Models\Character;

class CharacterAttackTransformer extends BaseTransformer {

    private bool $ignoreReductions = false;

    public function setIgnoreReductions(bool $ignoreReductions = false) {
        $this->ignoreReductions = $ignoreReductions;
    }

    /**
     * creates response data for character attack data.
     *
     * @param Character $character
     * @return array
     */
    public function transform(Character $character): array {

        $characterStatBuilder = resolve(CharacterStatBuilder::class)->setCharacter($character, $this->ignoreReductions);

        return CharacterAttackData::attackData($character, $characterStatBuilder);
    }
}
