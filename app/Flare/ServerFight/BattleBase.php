<?php

namespace App\Flare\ServerFight;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;

class BattleBase extends BattleMessages {

    protected int $characterHealth;

    protected int $monsterHealth;

    protected array $attackData;

    protected bool $isVoided;

    protected bool $isEnemyEntranced = false;

    protected bool $allowSecondaryAttacks = true;

    protected CharacterCacheData $characterCacheData;

    public function __construct(CharacterCacheData $characterCacheData)
    {
        parent::__construct();

        $this->characterCacheData = $characterCacheData;
    }

    public function setCharacterHealth(int $characterHealth)
    {
        $this->characterHealth = $characterHealth;
    }

    public function setMonsterHealth(int $monsterHealth)
    {
        $this->monsterHealth = $monsterHealth;
    }

    public function getCharacterHealth(): int
    {
        return $this->characterHealth;
    }

    public function getMonsterHealth(): int
    {
        return $this->monsterHealth;
    }

    public function doNotAllowSecondaryAttacks() {
        $this->allowSecondaryAttacks = false;
    }

    public function setEntranced() {
        $this->isEnemyEntranced = true;
    }

    protected function doPvpEntrance(Character $attacker, Entrance $entrance) {
        $entrance->attackerEntrancesDefender($attacker, $this->attackData, $this->isVoided);

        $this->mergeAttackerMessages($entrance->getAttackerMessages());
        $this->mergeDefenderMessages($entrance->getDefenderMessages());

        if ($entrance->isEnemyEntranced()) {
            $this->isEnemyEntranced = true;
        }
    }

    protected function doEnemyEntrance(Character $character, ServerMonster $monster, Entrance $entrance) {
        $entrance->playerEntrance($character, $monster, $this->attackData);

        $this->mergeMessages($entrance->getMessages());

        if ($entrance->isEnemyEntranced()) {
            $this->isEnemyEntranced = true;
        }
    }

    protected function secondaryAttack(Character $character, ServerMonster $monster = null, float $affixReduction = 0.0, bool $isPvp = false) {
        $secondaryAttacks = resolve(SecondaryAttacks::class);

        $secondaryAttacks->setMonsterHealth($this->monsterHealth);
        $secondaryAttacks->setCharacterHealth($this->characterHealth);
        $secondaryAttacks->setAttackData($this->attackData);
        $secondaryAttacks->setIsCharacterVoided($this->isVoided);
        $secondaryAttacks->setIsEnemyEntranced($this->isEnemyEntranced);

        $secondaryAttacks->doSecondaryAttack($character, $monster, $affixReduction, $isPvp);

        if ($isPvp) {
            $this->mergeAttackerMessages($secondaryAttacks->getAttackerMessages());
            $this->mergeDefenderMessages($secondaryAttacks->getDefenderMessages());
        } else {
            $secondaryAttacks->mergeMessages($secondaryAttacks->getMessages());
        }

        $secondaryAttacks->clearMessages();
    }

    protected function getPvpCharacterAc(Character $defender) {
        $defence = $this->characterCacheData->getCharacterDefenceAc($defender);

        if (!is_null($defence)) {
            return $defence;
        }

        return $this->characterCacheData->getCachedCharacterData($defender, 'ac');
    }

    protected function canBlock(int $damage, int $ac) {
        return $damage > $ac;
    }
}
