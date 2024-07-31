<?php

namespace App\Game\Character\Builders\AttackBuilders\Handler;

use Exception;
use App\Flare\Models\Character;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;

class UpdateCharacterAttackTypesHandler {

    /**
     * @var BuildCharacterAttackTypes $buildCharacterAttackTypes
     */
    private BuildCharacterAttackTypes $buildCharacterAttackTypes;

    /**
     * @param BuildCharacterAttackTypes $buildCharacterAttackTypes
     */
    public function __construct(BuildCharacterAttackTypes $buildCharacterAttackTypes) {
        $this->buildCharacterAttackTypes = $buildCharacterAttackTypes;
    }

    /**
     * @param Character $character
     * @param bool $ignoreReductions
     * @return void
     * @throws Exception
     */
    public function updateCache(Character $character, bool $ignoreReductions = false): void {
        $this->buildCharacterAttackTypes->buildCache($character, $ignoreReductions);

        $this->updateCharacterStats($character, $ignoreReductions);
    }

    /**
     * @param Character $character
     * @param bool $ignoreReductions
     * @return void
     */
    protected function updateCharacterStats(Character $character, bool $ignoreReductions = false): void {

        event(new UpdateCharacterAttackEvent($character, $ignoreReductions));
    }
}
