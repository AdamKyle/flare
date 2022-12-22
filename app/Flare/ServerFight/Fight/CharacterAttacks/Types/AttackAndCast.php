<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Affixes;
use App\Flare\ServerFight\Fight\CanHit;
use App\Flare\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;

class AttackAndCast extends BattleBase
{

    private Entrance $entrance;

    private CanHit $canHit;

    private SecondaryAttacks $secondaryAttacks;

    private WeaponType $weaponType;

    private CastType $castType;

    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit, SecondaryAttacks $secondaryAttacks, WeaponType $weaponType, CastType $castType)
    {
        parent::__construct($characterCacheData);

        $this->entrance              = $entrance;
        $this->canHit                = $canHit;
        $this->secondaryAttacks      = $secondaryAttacks;
        $this->weaponType            = $weaponType;
        $this->castType              = $castType;
    }

    public function setCharacterAttackData(Character $character, bool $isVoided): AttackAndCast
    {

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $isVoided ? 'voided_attack_and_cast' : 'attack_and_cast');
        $this->isVoided = $isVoided;

        return $this;
    }

    public function resetMessages()
    {
        $this->clearMessages();
        $this->entrance->clearMessages();
    }

    public function handlePvpAttack(Character $attacker, Character $defender) {

        $this->handlePvpWeaponAttack($attacker, $defender);
        $this->handlePvpCastAttack($attacker, $defender);

        $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($attacker, 'affix_damage_reduction'), true);

        return $this;
    }

    public function handleAttack(Character $character, ServerMonster $monster) {
        $this->handleWeaponAttack($character, $monster);
        $this->handleCastAttack($character, $monster);
        $this->secondaryAttack($character, $monster);

        return $this;
    }

    protected function handlePvpWeaponAttack(Character $attacker, Character $defender) {
        $this->doPvpEntrance($attacker, $this->entrance);

        $this->weaponType->setMonsterHealth($this->monsterHealth);
        $this->weaponType->setCharacterHealth($this->characterHealth);
        $this->weaponType->setCharacterAttackData($attacker, $this->isVoided);
        $this->weaponType->doNotAllowSecondaryAttacks();

        if ($this->isEnemyEntranced) {
            $this->weaponType->setEntranced();
        }

        $this->weaponType->doPvpWeaponAttack($attacker, $defender);

        $this->characterHealth = $this->weaponType->getCharacterHealth();
        $this->monsterHealth   = $this->weaponType->getMonsterHealth();

        $this->mergeAttackerMessages($this->weaponType->getAttackerMessages());
        $this->mergeDefenderMessages($this->weaponType->getDefenderMessages());

        $this->weaponType->resetMessages();
    }

    protected function handleWeaponAttack(Character $character, ServerMonster $monster) {
        $this->doEnemyEntrance($character, $monster, $this->entrance);

        $this->weaponType->setMonsterHealth($this->monsterHealth);
        $this->weaponType->setCharacterHealth($this->characterHealth);
        $this->weaponType->setCharacterAttackData($character, $this->isVoided);
        $this->weaponType->doNotAllowSecondaryAttacks();

        if ($this->isEnemyEntranced) {
            $this->weaponType->setEntranced();
        }

        $this->weaponType->doWeaponAttack($character, $monster);

        $this->mergeMessages($this->weaponType->getMessages());

        $this->characterHealth = $this->weaponType->getCharacterHealth();
        $this->monsterHealth   = $this->weaponType->getMonsterHealth();

        $this->weaponType->resetMessages();
    }

    protected function handlePvpCastAttack(Character $attacker, Character $defender) {
        $this->castType->setMonsterHealth($this->monsterHealth);
        $this->castType->setCharacterHealth($this->characterHealth);
        $this->castType->setCharacterAttackData($attacker, $this->isVoided);
        $this->castType->doNotAllowSecondaryAttacks();
        $this->castType->pvpCastAttack($attacker, $defender);

        $this->mergeMessages($this->castType->getMessages());

        $this->characterHealth = $this->castType->getCharacterHealth();
        $this->monsterHealth   = $this->castType->getMonsterHealth();

        $this->mergeAttackerMessages($this->castType->getAttackerMessages());
        $this->mergeDefenderMessages($this->castType->getDefenderMessages());

        $this->castType->resetMessages();
    }

    protected function handleCastAttack(Character $character, ServerMonster $monster) {
        $this->castType->setMonsterHealth($this->monsterHealth);
        $this->castType->setCharacterHealth($this->characterHealth);
        $this->castType->setCharacterAttackData($character, $this->isVoided);
        $this->castType->castAttack($character, $monster);

        $this->mergeMessages($this->castType->getMessages());

        $this->characterHealth = $this->castType->getCharacterHealth();
        $this->monsterHealth   = $this->castType->getMonsterHealth();

        $this->castType->resetMessages();
    }
}
