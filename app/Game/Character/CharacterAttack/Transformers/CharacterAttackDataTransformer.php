<?php

namespace App\Game\Character\CharacterAttack\Transformers;

use App\Flare\Models\Character;
use App\Flare\Transformers\BaseTransformer;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\CharacterAttack\DataSets\CharacterAttackData;

class CharacterAttackDataTransformer extends BaseTransformer
{
    private bool $ignoreReductions = false;

    public function setIgnoreReductions(bool $ignoreReductions): void
    {
        $this->ignoreReductions = $ignoreReductions;
    }

    /**
     * Gets the response data for the character sheet
     */
    public function transform(Character $character): array
    {
        $characterStatBuilder = resolve(CharacterStatBuilder::class)->setCharacter($character, $this->ignoreReductions);

        $characterAttackData = resolve(CharacterAttackData::class);

        $characterAttackData->setIncludeReductions($this->ignoreReductions);

        return $characterAttackData->attackData($character, $characterStatBuilder);
    }
}
