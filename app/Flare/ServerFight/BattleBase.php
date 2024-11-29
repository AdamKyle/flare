<?php

namespace App\Flare\ServerFight;

use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\CharacterAttacks\Counter;
use App\Flare\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Flare\ServerFight\Fight\ElementalAttack;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class BattleBase extends BattleMessages
{
    protected int $characterHealth;

    protected int $monsterHealth;

    protected ?int $defenderId = null;

    protected array $attackData;

    protected bool $isVoided = false;

    protected bool $isEnemyVoided = false;

    protected bool $isEnemyEntranced = false;

    protected bool $allowSecondaryAttacks = true;

    protected bool $abortCharacterIsDead = false;

    protected bool $isRaidBoss = false;

    protected CharacterCacheData $characterCacheData;

    const MAX_DAMAGE_FOR_RAID_BOSSES = 2_000_000_000_000;

    const MINIMUM_DAMAGE_FOR_A_PLAYER = 500_000_000;

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

    public function doNotAllowSecondaryAttacks()
    {
        $this->allowSecondaryAttacks = false;
    }

    public function setEntranced()
    {
        $this->isEnemyEntranced = true;
    }

    public function setIsEnemyVoided(bool $isVoided)
    {
        $this->isEnemyVoided = $isVoided;
    }

    public function setDefenderId(int $defenderId)
    {
        $this->defenderId = $defenderId;
    }

    public function setIsRaidBoss(bool $isRaidBoss)
    {
        $this->isRaidBoss = $isRaidBoss;
    }

    protected function doEnemyEntrance(Character $character, ServerMonster $monster, Entrance $entrance)
    {
        $entrance->playerEntrance($character, $monster, $this->attackData);

        $this->mergeMessages($entrance->getMessages());

        if ($entrance->isEnemyEntranced()) {
            $this->isEnemyEntranced = true;
        }
    }

    protected function secondaryAttack(Character $character, ?ServerMonster $monster = null, float $affixReduction = 0.0)
    {
        $secondaryAttacks = resolve(SecondaryAttacks::class);

        $secondaryAttacks->setIsRaidBoss($this->isRaidBoss);
        $secondaryAttacks->setMonsterHealth($this->monsterHealth);
        $secondaryAttacks->setCharacterHealth($this->characterHealth);
        $secondaryAttacks->setAttackData($this->attackData);
        $secondaryAttacks->setIsCharacterVoided($this->isVoided);
        $secondaryAttacks->setIsEnemyEntranced($this->isEnemyEntranced);
        $secondaryAttacks->setDefenderId(is_null($this->defenderId) ? $monster->getId() : $this->defenderId);

        $secondaryAttacks->doSecondaryAttack($character, $monster, $affixReduction);

        $this->monsterHealth = $secondaryAttacks->getMonsterHealth();
        $this->characterHealth = $secondaryAttacks->getCharacterHealth();

        $this->mergeMessages($secondaryAttacks->getMessages());

        $secondaryAttacks->clearMessages();
    }

    protected function elementalAttack(Character $character, ServerMonster $monster, string $damageType)
    {

        $elementalAttack = resolve(ElementalAttack::class);

        $elementalAttack->setMonsterHealth($this->monsterHealth);
        $elementalAttack->setCharacterHealth($this->characterHealth);
        $elementalAttack->setIsRaidBoss($this->isRaidBoss);

        $characterElementalData = $this->characterCacheData->getCachedCharacterData($character, 'elemental_atonement');

        if (is_null($characterElementalData)) {
            return;
        }

        $characterElementalData = $characterElementalData['atonements'];

        $damage = $this->characterCacheData->getCachedCharacterData($character, $damageType);

        $elementalAttack->doElementalAttack($monster->getElementData(), $characterElementalData, $damage);

        $this->mergeMessages($elementalAttack->getMessages());

        $this->characterHealth = $elementalAttack->getCharacterHealth();
        $this->monsterHealth = $elementalAttack->getMonsterHealth();

        $elementalAttack->clearMessages();
    }

    protected function doMonsterCounter(Character $character, ServerMonster $monster)
    {

        if ($this->getMonsterHealth() < 0) {
            return;
        }

        $counter = resolve(Counter::class);

        $counter->setCharacterHealth($this->characterHealth);
        $counter->setMonsterHealth($this->monsterHealth);
        $counter->setIsAttackerVoided($this->isVoided);
        $counter->monsterCounter($character, $monster);

        $this->mergeMessages($counter->getMessages());

        $this->characterHealth = $counter->getCharacterHealth();
        $this->monsterHealth = $counter->getMonsterHealth();

        $counter->clearMessages();
    }

    protected function doPlayerCounterMonster(Character $character, ServerMonster $monster)
    {
        $counter = resolve(Counter::class);

        $counter->setCharacterHealth($this->characterHealth);
        $counter->setMonsterHealth($this->monsterHealth);
        $counter->setIsAttackerVoided($this->isVoided);
        $counter->playerCounter($character, $monster);

        $this->mergeMessages($counter->getMessages());

        $this->characterHealth = $counter->getCharacterHealth();
        $this->monsterHealth = $counter->getMonsterHealth();

        $counter->clearMessages();
    }

    protected function canBlock(int $damage, ServerMonster $serverMonster)
    {

        if ($serverMonster->isRaidBossMonster() && $damage < self::MINIMUM_DAMAGE_FOR_A_PLAYER) {
            $this->addMessage(
                'The enemy laughs at you. "Child your attacks mean nothing to me. Go on, give it your best shot!"',
                'enemy-action'
            );

            return false;
        }

        return $serverMonster->getMonsterStat('ac') > $damage;
    }
}
