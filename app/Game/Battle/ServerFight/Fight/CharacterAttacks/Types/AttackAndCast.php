<?php

namespace App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\BattleBase;
use App\Game\Battle\ServerFight\Fight\Entrance;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Combat\Values\AttackType;

class AttackAndCast extends BattleBase
{
    private string $castAttackType;

    public function __construct(
        CharacterCacheData $characterCacheData,
        ChanceCalculator $chanceCalculator,
        RandomNumberGenerator $randomNumberGenerator,
        private Entrance $entrance,
        private WeaponType $weaponType,
        private CastType $castType
    ) {
        parent::__construct($characterCacheData, $chanceCalculator, $randomNumberGenerator);
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

        return $this;
    }

    /**
     * Perform the weapon-attack half of an attack-and-cast/cast-and-attack turn.
     *
     * Protected so `CastAndAttack` (the only subclass) can call it directly
     * to reorder the weapon/cast halves for its own turn sequence.
     */
    protected function handleWeaponAttack(Character $character, ServerMonster $monster, bool $disableSecondaryAttacks = true)
    {
        if (! $this->isEnemyEntranced) {
            $this->doEnemyEntrance($character, $monster, $this->entrance);
        }

        $this->weaponType->setMonsterHealth($this->monsterHealth);
        $this->weaponType->setCharacterHealth($this->characterHealth);
        $this->weaponType->setCharacterAttackData($character, $this->isVoided, AttackType::ATTACK_AND_CAST->value);

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

    /**
     * Perform the cast half of an attack-and-cast/cast-and-attack turn.
     *
     * Protected so `CastAndAttack` (the only subclass) can call it directly
     * to reorder the weapon/cast halves for its own turn sequence.
     */
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
