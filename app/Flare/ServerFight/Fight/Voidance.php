<?php

namespace App\Flare\ServerFight\Fight;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\BattleMessages;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Flare\Values\MapNameValue;

class Voidance extends BattleMessages {

    private bool $characterIsVoided;

    public function __construct() {
        parent::__construct();

        $this->characterIsVoided = false;
    }

    public function void(Character $character, CharacterCacheData $characterCacheData, ServerMonster $monster) {
        $mapNameValue = $character->map->gameMap->mapType();

        if ($mapNameValue->isSurface()) {
            $this->characterVoidsFirst($character, $characterCacheData, $monster);
        } else if ($mapNameValue->isHell() || $mapNameValue->isPurgatory()) {
            $this->monsterVoidsFirst($character, $characterCacheData, $monster);
        }
    }

    public function isPlayerVoided(): bool {
        return $this->characterIsVoided;
    }

    protected function characterVoidsFirst(Character $character, CharacterCacheData $characterCacheData, ServerMonster $monster) {
        $monsterDevoided = false;
        $monsterVoided   = false;
        $playerDevoided  = false;

        if ($this->canPlayerDeVoidEnemy($characterCacheData->getCachedCharacterData($character, 'devouring_darkness'))) {
            $this->addMessage('Magic crackles in the air, the darkness consumes the enemy. They are devoided!', 'regular');

            $monsterDevoided = true;
        }

        if ($monster->canMonsterDevoidPlayer($characterCacheData->getCachedCharacterData($character, 'devouring_darkness_res')) && !$monsterDevoided) {
            $this->addMessage($monster->getName() . ' has devoided your voidance! You feel fear start to build.', 'enemy-action');

            $playerDevoided = true;
        }

        if ($this->canPlayerVoidEnemy($characterCacheData->getCachedCharacterData($character, 'devouring_darkness')) && !$playerDevoided) {
            $this->addMessage('The light of the heavens shines through this darkness. The enemy is voided!', 'regular');

            $monsterVoided = true;
        }

        if ($monster->canMonsterVoidPlayer($characterCacheData->getCachedCharacterData($character, 'devouring_light_res')) && (!$monsterVoided || !$monsterDevoided)) {
            $this->addMessage($monster->getName() . ' has voided your enchantments! You feel much weaker!', 'enemy-action');

            $this->characterIsVoided = true;
        }
    }

    protected function monsterVoidsFirst(Character $character, CharacterCacheData $characterCacheData, ServerMonster $monster) {
        $monsterDevoided = false;
        $playerDevoided  = false;

        if ($monster->canMonsterDevoidPlayer($characterCacheData->getCachedCharacterData($character, 'devouring_darkness_res'))) {
            $this->addMessage($monster->getName() . ' has devoided your voidance! You feel fear start to build.', 'enemy-action');

            $playerDevoided = true;
        }

        if ($this->canPlayerDeVoidEnemy($characterCacheData->getCachedCharacterData($character, 'devouring_darkness')) && !$playerDevoided) {
            $this->addMessage('Magic crackles in the air, the darkness consumes the enemy. They are devoided!', 'regular');

            $monsterDevoided = true;
        }

        if ($monster->canMonsterVoidPlayer($characterCacheData->getCachedCharacterData($character, 'devouring_light_res')) && !$monsterDevoided) {
            $this->addMessage($monster->getName() . ' has voided your enchantments! You feel much weaker!', 'enemy-action');

            $this->characterIsVoided = true;
        }


        if ($this->canPlayerVoidEnemy($characterCacheData->getCachedCharacterData($character, 'devouring_darkness')) && (!$playerDevoided || !$this->characterIsVoided)) {
            $this->addMessage('The light of the heavens shines through this darkness. The enemy is voided!', 'regular');
        }
    }

    private function canPlayerVoidEnemy(float $voidanceChance): bool {
        if ($voidanceChance > 1) {
            return true;
        }

        $roll = rand(1, 100);

        return $roll > (100 - 100 * $voidanceChance);
    }

    private function canPlayerDeVoidEnemy(float $deVoidanceChance): bool {
        if ($deVoidanceChance > 1) {
            return true;
        }

        $roll = rand(1, 100);

        return $roll > (100 - 100 * $deVoidanceChance);
    }
}
