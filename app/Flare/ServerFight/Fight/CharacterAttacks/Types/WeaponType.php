<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Attack;
use App\Flare\ServerFight\Fight\CanHit;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Flare\Values\AttackTypeValue;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class WeaponType extends BattleBase
{
    private Entrance $entrance;

    private CanHit $canHit;

    private SpecialAttacks $specialAttacks;

    private bool $canEntrance = false;

    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit, SpecialAttacks $specialAttacks)
    {
        parent::__construct($characterCacheData);

        $this->entrance = $entrance;
        $this->canHit = $canHit;
        $this->specialAttacks = $specialAttacks;
    }

    public function setCharacterAttackData(Character $character, bool $isVoided, string $type): WeaponType
    {

        $voidedTypes = [
            AttackTypeValue::VOIDED_ATTACK,
            AttackTypeValue::VOIDED_CAST,
            AttackTypeValue::VOIDED_ATTACK_AND_CAST,
            AttackTypeValue::VOIDED_CAST_AND_ATTACK,
            AttackTypeValue::VOIDED_DEFEND,
        ];

        if ($isVoided && !in_array($type, $voidedTypes)) {
            $attackType = 'voided_' . $type;
        } else {
            $attackType = $type;
        }

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $attackType);
        $this->isVoided = $isVoided;

        return $this;
    }

    public function setAllowEntrancing(bool $allowEntrance): WeaponType
    {
        $this->canEntrance = $allowEntrance;

        return $this;
    }

    public function doWeaponAttack(Character $character, ServerMonster $serverMonster): WeaponType
    {
        $weaponDamage = $this->attackData['weapon_damage'];

        $weaponDamage = $this->getCriticalityDamage($character, $weaponDamage);

        if (! $this->isEnemyEntranced && $this->canEntrance) {
            $this->doEnemyEntrance($character, $serverMonster, $this->entrance);
        }

        if ($this->isEnemyEntranced) {
            $this->weaponAttack($character, $serverMonster, $weaponDamage);

            return $this;
        }

        if ($this->canHit->canPlayerAutoHit($character)) {
            $this->addMessage('You dance along in the shadows, the enemy doesn\'t see you. Strike now!', 'regular');

            $this->weaponAttack($character, $serverMonster, $weaponDamage);

            return $this;
        }

        if ($this->canHit->canPlayerHitMonster($character, $serverMonster, $this->isVoided)) {

            if ($this->canBlock($weaponDamage, $serverMonster)) {
                $this->addMessage('Your weapon was blocked!', 'enemy-action');

                $this->dealSecondaryAttackDamage($character, $serverMonster);
            } else {
                $this->weaponAttack($character, $serverMonster, $weaponDamage);
            }
        } else {
            $this->addMessage('Your attack missed!', 'enemy-action');

            $this->dealSecondaryAttackDamage($character, $serverMonster);
        }

        return $this;
    }

    protected function dealSecondaryAttackDamage(Character $character, ?ServerMonster $serverMonster = null): void
    {
        if ($this->allowSecondaryAttacks && ! $this->abortCharacterIsDead) {
            $this->secondaryAttack($character, $serverMonster);
        }
    }

    public function resetMessages()
    {
        $this->clearMessages();
        $this->entrance->clearMessages();
    }

    public function weaponAttack(Character $character, ServerMonster $monster, int $weaponDamage)
    {
        $this->weaponDamage($character, $monster->getName(), $weaponDamage);

        if (! $this->isEnemyEntranced) {
            $this->doMonsterCounter($character, $monster);
        }

        if ($this->characterHealth <= 0) {
            $this->abortCharacterIsDead = true;

            return;
        }

        $this->dealSecondaryAttackDamage($character, $monster);
    }

    public function weaponDamage(Character $character, string $monsterName, int $weaponDamage)
    {
        $totalDamage = $weaponDamage - $weaponDamage * $this->attackData['damage_deduction'];

        if ($this->isRaidBoss && $totalDamage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
            $totalDamage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
        }

        $this->monsterHealth -= $totalDamage;

        $this->addMessage('Your weapon hits ' . $monsterName . ' for: ' . number_format($totalDamage), 'player-action');

        $this->specialAttacks->setCharacterHealth($this->characterHealth)
            ->setMonsterHealth($this->monsterHealth)
            ->setIsRaidBoss($this->isRaidBoss)
            ->doWeaponSpecials($character, $this->attackData);

        $this->mergeMessages($this->specialAttacks->getMessages());

        $this->characterHealth = $this->specialAttacks->getCharacterHealth();
        $this->monsterHealth = $this->specialAttacks->getMonsterHealth();

        $this->specialAttacks->clearMessages();
    }

    protected function getCriticalityDamage(Character $character, int $weaponDamage)
    {
        $criticality = $this->characterCacheData->getCachedCharacterData($character, 'skills')['criticality'];

        if (rand(1, 100) > (100 - 100 * $criticality)) {
            $this->addMessage('You become overpowered with rage! (Critical strike!)', 'player-action');

            $weaponDamage *= 2;
        }

        return $weaponDamage;
    }
}
