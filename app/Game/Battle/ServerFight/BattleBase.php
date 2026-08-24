<?php

namespace App\Game\Battle\ServerFight;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\Fight\Entrance;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;

class BattleBase extends BattleMessages
{
    /**
     * Shared combat state read and written directly by every BattleBase
     * subclass across a fight (health totals, void/entrance/raid flags,
     * the active attack payload, and the cache collaborator).
     */
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

    public function __construct(
        CharacterCacheData $characterCacheData,
        protected readonly ChanceCalculator $chanceCalculator,
        protected readonly RandomNumberGenerator $randomNumberGenerator,
    ) {
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

    /**
     * Resolve whether the enemy entrances the player for this turn.
     *
     * Protected so attack-type subclasses (WeaponType, CastType,
     * AttackAndCast) can trigger it before running their own attack.
     */
    protected function doEnemyEntrance(Character $character, ServerMonster $monster, Entrance $entrance)
    {
        $entrance->playerEntrance($character, $monster, $this->attackData);

        $this->mergeMessages($entrance->getMessages());

        if ($entrance->isEnemyEntranced()) {
            $this->isEnemyEntranced = true;
        }
    }

    /**
     * Resolve whether the enemy blocks a computed damage value.
     *
     * Protected so WeaponType (the only current caller) can check this
     * before applying weapon damage.
     */
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

    /**
     * Truncate a computed damage value down to a whole number of health points.
     *
     * Damage values are always non-negative, so truncating toward zero is
     * equivalent to `floor()`; this is the shared conversion boundary used
     * by every special attack before subtracting from health.
     */
    protected function truncatedDamage(float $damage): int
    {
        return intval(floor($damage));
    }
}
