<?php

namespace App\Flare\ServerFight\Fight;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Monster\ServerMonster;

class Entrance extends BattleBase {

    private bool $isEnemyEntranced;

    private bool $isCharacterEntranced;

    public function __construct(CharacterCacheData $characterCacheData) {
        parent::__construct($characterCacheData);

        $this->isEnemyEntranced     = false;
        $this->isCharacterEntranced = false;
    }

    public function isCharacterEntracned() {
        return $this->isCharacterEntranced;
    }

    public function isEnemyEntranced() {
        return $this->isEnemyEntranced;
    }

    public function playerEntrance(Character $character, ServerMonster $monster, array $attackType) {
        if ($attackType['affixes']['entrancing_chance'] > 0.0) {
            if ($this->canPlayerEntranceMonster($character, $monster, $attackType)) {
                $this->addMessage('The enemy is dazed by your enchantments!', 'player-action');

                $this->isEnemyEntranced = true;
            } else {
                $this->addMessage('The enemy resists your entrancing enchantments!', 'enemy-action');
            }
        }
    }

    public function monsterEntrancesPlayer(Character $character, ServerMonster $monster, bool $isPlayerVoided) {
        if ($this->canMonsterEntrancePlayer($character, $monster, $isPlayerVoided)) {
            $this->addMessage($monster->getName() . ' has trapped you in a trance-like state with their enchantments!', 'enemy-action');

            $this->isCharacterEntranced = true;
        } else {
            $this->addMessage('You resist the alluring entrancing enchantments on your enemy!', 'player-action');
        }
    }

    protected function canPlayerEntranceMonster(Character $character, ServerMonster $monster, array $attackType): bool {
        $chance = $attackType['affixes']['entrancing_chance'] - $monster->getMonsterStat('affix_resistance');

        if ($attackType['affixes']['cant_be_resisted']) {
            return true;
        }

        if ($chance > 1) {
            return true;
        }

        $roll = rand(1, 100);

        return ($roll + $roll * $chance) > 50;
    }

    protected function canMonsterEntrancePlayer(Character $character, ServerMonster $monster, bool $isPlayerVoided): bool {

        $chance = $monster->getMonsterStat('entrance_chance');

        if ($chance > 1) {
            return true;
        }

        $roll = rand(1, 100);

        $dc = 50;

        if ($character->classType()->isProphet() || $character->classType()->isHeretic()) {
            $dc = ceil($this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? 'voided_focus' : 'focus') * 0.05);
        }

        return ($roll + $roll * $chance) > $dc;
    }
}
