<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\AttackAndCast;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\CastAndAttack;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\CastType;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\Defend;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\WeaponType;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Flare\Values\AttackTypeValue;

class CharacterAttack
{
    private mixed $type;

    public function __construct(
        private WeaponType $weaponType,
        private CastType $castType,
        private AttackAndCast $attackAndCast,
        private CastAndAttack $castAndAttack,
        private Defend $defend
    ) {}

    public function attack(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack
    {
        $this->weaponType->setIsRaidBoss($monster->isRaidBossMonster());
        $this->weaponType->setCharacterHealth($characterHealth);
        $this->weaponType->setMonsterHealth($monsterHealth);
        $this->weaponType->setCharacterAttackData($character, $isPlayerVoided, AttackTypeValue::ATTACK);
        $this->weaponType->setAllowEntrancing(true);
        $this->weaponType->doWeaponAttack($character, $monster);

        $this->type = $this->weaponType;

        return $this;
    }

    public function cast(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack
    {
        $this->castType->setIsRaidBoss($monster->isRaidBossMonster());
        $this->castType->setCharacterHealth($characterHealth);
        $this->castType->setMonsterHealth($monsterHealth);
        $this->castType->setCharacterAttackData($character, $isPlayerVoided, AttackTypeValue::CAST);
        $this->castType->setAllowEntrancing(true);

        $this->castType->castAttack($character, $monster);

        $this->type = $this->castType;

        return $this;
    }

    public function attackAndCast(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack
    {
        $this->attackAndCast->setIsRaidBoss($monster->isRaidBossMonster());
        $this->attackAndCast->setCharacterHealth($characterHealth);
        $this->attackAndCast->setMonsterHealth($monsterHealth);
        $this->attackAndCast->setCharacterAttackData($character, $isPlayerVoided, AttackTypeValue::ATTACK_AND_CAST);
        $this->attackAndCast->handleAttack($character, $monster);

        $this->type = $this->attackAndCast;

        return $this;
    }

    public function castAndAttack(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack
    {
        $this->castAndAttack->setIsRaidBoss($monster->isRaidBossMonster());
        $this->castAndAttack->setCharacterHealth($characterHealth);
        $this->castAndAttack->setMonsterHealth($monsterHealth);
        $this->castAndAttack->setCharacterCastAndAttackkData($character, $isPlayerVoided);
        $this->castAndAttack->handleAttack($character, $monster);

        $this->type = $this->castAndAttack;

        return $this;
    }

    public function defend(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack
    {
        $this->defend->setIsRaidBoss($monster->isRaidBossMonster());
        $this->defend->setCharacterHealth($characterHealth);
        $this->defend->setMonsterHealth($monsterHealth);
        $this->defend->setCharacterAttackData($character, $isPlayerVoided);
        $this->defend->defend($character, $monster);

        $this->type = $this->defend;

        return $this;
    }

    public function getMessages()
    {
        return $this->type->getMessages();
    }

    public function resetMessages()
    {
        $this->type->resetMessages();
    }

    public function getCharacterHealth()
    {
        return $this->type->getCharacterHealth();
    }

    public function getMonsterHealth()
    {
        return $this->type->getMonsterHealth();
    }
}
