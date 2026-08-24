<?php

namespace App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\BattleBase;
use App\Game\Battle\ServerFight\Fight\CanHit;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Counter;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;
use App\Game\Battle\ServerFight\Fight\Entrance;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Combat\Values\AttackType;

class WeaponType extends BattleBase
{
    private Entrance $entrance;

    private CanHit $canHit;

    private SpecialAttacks $specialAttacks;

    private SecondaryAttacks $secondaryAttacks;

    private Counter $counter;

    private bool $canEntrance = false;

    public function __construct(CharacterCacheData $characterCacheData, ChanceCalculator $chanceCalculator, RandomNumberGenerator $randomNumberGenerator, Entrance $entrance, CanHit $canHit, SpecialAttacks $specialAttacks, SecondaryAttacks $secondaryAttacks, Counter $counter)
    {
        parent::__construct($characterCacheData, $chanceCalculator, $randomNumberGenerator);

        $this->entrance = $entrance;
        $this->canHit = $canHit;
        $this->specialAttacks = $specialAttacks;
        $this->secondaryAttacks = $secondaryAttacks;
        $this->counter = $counter;
    }

    public function setCharacterAttackData(Character $character, bool $isVoided, string $type): WeaponType
    {

        $voidedTypes = [
            AttackType::VOIDED_ATTACK->value,
            AttackType::VOIDED_CAST->value,
            AttackType::VOIDED_ATTACK_AND_CAST->value,
            AttackType::VOIDED_CAST_AND_ATTACK->value,
            AttackType::VOIDED_DEFEND->value,
        ];

        if ($isVoided && ! in_array($type, $voidedTypes)) {
            $attackType = 'voided_'.$type;
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

    private function dealSecondaryAttackDamage(Character $character, ?ServerMonster $serverMonster = null): void
    {
        if ($this->allowSecondaryAttacks && ! $this->abortCharacterIsDead) {
            $this->secondaryAttack($character, $serverMonster);
        }
    }

    /**
     * Run the character's secondary attack against the monster.
     */
    private function secondaryAttack(Character $character, ?ServerMonster $monster = null, float $affixReduction = 0.0): void
    {
        $this->secondaryAttacks->setIsRaidBoss($this->isRaidBoss);
        $this->secondaryAttacks->setMonsterHealth($this->monsterHealth);
        $this->secondaryAttacks->setCharacterHealth($this->characterHealth);
        $this->secondaryAttacks->setAttackData($this->attackData);
        $this->secondaryAttacks->setIsCharacterVoided($this->isVoided);
        $this->secondaryAttacks->setIsEnemyEntranced($this->isEnemyEntranced);
        $this->secondaryAttacks->setDefenderId(is_null($this->defenderId) ? $monster->getId() : $this->defenderId);

        $this->secondaryAttacks->doSecondaryAttack($character, $monster, $affixReduction);

        $this->monsterHealth = $this->secondaryAttacks->getMonsterHealth();
        $this->characterHealth = $this->secondaryAttacks->getCharacterHealth();

        $this->mergeMessages($this->secondaryAttacks->getMessages());

        $this->secondaryAttacks->clearMessages();
    }

    /**
     * Let the monster counter the character's attack.
     */
    private function doMonsterCounter(Character $character, ServerMonster $monster): void
    {
        if ($this->getMonsterHealth() <= 0) {
            return;
        }

        $this->counter->setCharacterHealth($this->characterHealth);
        $this->counter->setMonsterHealth($this->monsterHealth);
        $this->counter->setIsAttackerVoided($this->isVoided);
        $this->counter->monsterCounter($character, $monster);

        $this->mergeMessages($this->counter->getMessages());

        $this->characterHealth = $this->counter->getCharacterHealth();
        $this->monsterHealth = $this->counter->getMonsterHealth();

        $this->counter->clearMessages();
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

        $this->addMessage('Your weapon hits '.$monsterName.' for: '.number_format($totalDamage), 'player-action');

        $this->specialAttacks->setCharacterHealth($this->characterHealth)
            ->setMonsterHealth($this->monsterHealth)
            ->setIsRaidBoss($this->isRaidBoss)
            ->doWeaponSpecials($character, $this->attackData);

        $this->mergeMessages($this->specialAttacks->getMessages());

        $this->characterHealth = $this->specialAttacks->getCharacterHealth();
        $this->monsterHealth = $this->specialAttacks->getMonsterHealth();

        $this->specialAttacks->clearMessages();
    }

    private function getCriticalityDamage(Character $character, int $weaponDamage)
    {
        $criticality = $this->characterCacheData->getCachedCharacterData($character, 'skills')['criticality'];

        if ($this->chanceCalculator->passesPercentage($criticality * 100)) {
            $this->addMessage('You become overpowered with rage! (Critical strike!)', 'player-action');

            $weaponDamage *= 2;
        }

        return $weaponDamage;
    }
}
