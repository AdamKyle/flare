<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Facades\App\Flare\Transformers\DataSets\CharacterAttackData;

class CharacterAttackTransformer extends BaseTransformer
{
    private bool $ignoreReductions = false;

    public function setIgnoreReductions(bool $ignoreReductions = false)
    {
        $this->ignoreReductions = $ignoreReductions;
    }

    /**
     * creates response data for character attack data.
     */
    public function transform(Character $character): array
    {

        $characterStatBuilder = resolve(CharacterStatBuilder::class)->setCharacter($character, $this->ignoreReductions);

        return CharacterAttackData::attackData($character, $characterStatBuilder);
    }
}
