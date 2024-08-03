<?php

namespace App\Game\Character\Builders\AttackBuilders\Handler;

use App\Flare\Models\Character;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use Exception;

class UpdateCharacterAttackTypesHandler
{
    private BuildCharacterAttackTypes $buildCharacterAttackTypes;

    public function __construct(BuildCharacterAttackTypes $buildCharacterAttackTypes)
    {
        $this->buildCharacterAttackTypes = $buildCharacterAttackTypes;
    }

    /**
     * @throws Exception
     */
    public function updateCache(Character $character, bool $ignoreReductions = false): void
    {
        $this->buildCharacterAttackTypes->buildCache($character, $ignoreReductions);

        $this->updateCharacterStats($character, $ignoreReductions);
    }

    protected function updateCharacterStats(Character $character, bool $ignoreReductions = false): void
    {

        event(new UpdateCharacterAttackEvent($character, $ignoreReductions));
    }
}
