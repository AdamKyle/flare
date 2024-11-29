<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Flare\Values\AttackTypeValue;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class AttackAndCast extends BattleBase
{

    private string $castAttackType;

    public function __construct(
        CharacterCacheData $characterCacheData,
        private Entrance $entrance,
        private WeaponType $weaponType,
        private CastType $castType
    ) {
        parent::__construct($characterCacheData);
    }

    public function setWhichCastType(string $type = 'attack_and_cast')
    {
        $this->castAttackType = $type;
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

    public function handleAttack(Character $character, ServerMonster $monster)
    {
        $this->handleWeaponAttack($character, $monster, false);

        if ($this->characterHealth <= 0) {
            return $this;
        }

        $this->setWhichCastType('attack_and_cast');
        $this->handleCastAttack($character, $monster);

        if ($this->characterHealth <= 0) {
            return $this;
        }

        return $this;
    }

    protected function handleWeaponAttack(Character $character, ServerMonster $monster, bool $disableSecondaryAttacks = true)
    {
        if (! $this->isEnemyEntranced) {
            $this->doEnemyEntrance($character, $monster, $this->entrance);
        }

        $this->weaponType->setMonsterHealth($this->monsterHealth);
        $this->weaponType->setCharacterHealth($this->characterHealth);
        $this->weaponType->setCharacterAttackData($character, $this->isVoided, AttackTypeValue::ATTACK_AND_CAST);

        if ($disableSecondaryAttacks) {
            $this->weaponType->doNotAllowSecondaryAttacks();
        }

        if ($this->isEnemyEntranced) {
            $this->weaponType->setEntranced();
        }

        $this->weaponType->doWeaponAttack($character, $monster);

        $this->mergeMessages($this->weaponType->getMessages());

        $this->characterHealth = $this->weaponType->getCharacterHealth();
        $this->monsterHealth = $this->weaponType->getMonsterHealth();

        $this->weaponType->resetMessages();
    }

    protected function handleCastAttack(Character $character, ServerMonster $monster, bool $disableSecondaryAttacks = true)
    {

        if (! $this->isEnemyEntranced) {
            $this->doEnemyEntrance($character, $monster, $this->entrance);
        }

        $this->castType->setMonsterHealth($this->monsterHealth);
        $this->castType->setCharacterHealth($this->characterHealth);

        if ($this->castAttackType === 'cast_and_attack') {
            $this->castType->setCharacterCastAndAttack($character, $this->isVoided);
        }

        if ($this->castAttackType === 'attack_and_cast') {
            $this->castType->setCharacterAttackAndCast($character, $this->isVoided);
        }

        if ($disableSecondaryAttacks) {
            $this->castType->doNotAllowSecondaryAttacks();
        }

        if ($this->isEnemyEntranced) {
            $this->castType->setEntranced();
        }

        $this->castType->castAttack($character, $monster);

        $this->mergeMessages($this->castType->getMessages());

        $this->characterHealth = $this->castType->getCharacterHealth();
        $this->monsterHealth = $this->castType->getMonsterHealth();

        $this->castType->resetMessages();
    }
}
