<?php

namespace App\Game\Battle\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\AttackAndCast;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\CastAndAttack;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\CastType;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\Defend;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\WeaponType;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Combat\Values\AttackType;

class CharacterAttack
{
    private mixed $type;

    /**
     * @param WeaponType $weaponType
     * @param CastType $castType
     * @param AttackAndCast $attackAndCast
     * @param CastAndAttack $castAndAttack
     * @param Defend $defend
     * @param GemAbilityExecutor $gemAbilityExecutor
     */
    public function __construct(
        private WeaponType $weaponType,
        private CastType $castType,
        private AttackAndCast $attackAndCast,
        private CastAndAttack $castAndAttack,
        private Defend $defend,
        private GemAbilityExecutor $gemAbilityExecutor,
    ) {}

    /**
     * Execute a top-level weapon attack and its applicable active Gem Abilities.
     *
     * @param Character $character
     * @param ServerMonster $monster
     * @param bool $isPlayerVoided
     * @param int $characterHealth
     * @param int $monsterHealth
     * @return CharacterAttack
     */
    public function attack(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack
    {
        $this->weaponType->setIsRaidBoss($monster->isRaidBossMonster());
        $this->weaponType->setCharacterHealth($characterHealth);
        $this->weaponType->setMonsterHealth($monsterHealth);
        $this->weaponType->setCharacterAttackData($character, $isPlayerVoided, AttackType::ATTACK->value);
        $this->weaponType->setAllowEntrancing(true);
        $this->weaponType->doWeaponAttack($character, $monster);

        $this->type = $this->weaponType;
        $this->executeGemAbilities($character, $monster, AttackType::ATTACK->value, $isPlayerVoided);

        return $this;
    }

    /**
     * Execute a top-level cast and its applicable active Gem Abilities.
     *
     * @param Character $character
     * @param ServerMonster $monster
     * @param bool $isPlayerVoided
     * @param int $characterHealth
     * @param int $monsterHealth
     * @return CharacterAttack
     */
    public function cast(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack
    {
        $this->castType->setIsRaidBoss($monster->isRaidBossMonster());
        $this->castType->setCharacterHealth($characterHealth);
        $this->castType->setMonsterHealth($monsterHealth);
        $this->castType->setCharacterAttackData($character, $isPlayerVoided, AttackType::CAST->value);
        $this->castType->setAllowEntrancing(true);

        $this->castType->castAttack($character, $monster);

        $this->type = $this->castType;
        $this->executeGemAbilities($character, $monster, AttackType::CAST->value, $isPlayerVoided);

        return $this;
    }

    /**
     * Execute a top-level attack-and-cast action and its applicable active Gem Abilities.
     *
     * @param Character $character
     * @param ServerMonster $monster
     * @param bool $isPlayerVoided
     * @param int $characterHealth
     * @param int $monsterHealth
     * @return CharacterAttack
     */
    public function attackAndCast(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack
    {
        $this->attackAndCast->setIsRaidBoss($monster->isRaidBossMonster());
        $this->attackAndCast->setCharacterHealth($characterHealth);
        $this->attackAndCast->setMonsterHealth($monsterHealth);
        $this->attackAndCast->setCharacterAttackData($character, $isPlayerVoided, AttackType::ATTACK_AND_CAST->value);
        $this->attackAndCast->handleAttack($character, $monster);

        $this->type = $this->attackAndCast;
        $this->executeGemAbilities($character, $monster, AttackType::ATTACK_AND_CAST->value, $isPlayerVoided);

        return $this;
    }

    /**
     * Execute a top-level cast-and-attack action and its applicable active Gem Abilities.
     *
     * @param Character $character
     * @param ServerMonster $monster
     * @param bool $isPlayerVoided
     * @param int $characterHealth
     * @param int $monsterHealth
     * @return CharacterAttack
     */
    public function castAndAttack(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack
    {
        $this->castAndAttack->setIsRaidBoss($monster->isRaidBossMonster());
        $this->castAndAttack->setCharacterHealth($characterHealth);
        $this->castAndAttack->setMonsterHealth($monsterHealth);
        $this->castAndAttack->setCharacterCastAndAttackkData($character, $isPlayerVoided);
        $this->castAndAttack->handleAttack($character, $monster);

        $this->type = $this->castAndAttack;
        $this->executeGemAbilities($character, $monster, AttackType::CAST_AND_ATTACK->value, $isPlayerVoided);

        return $this;
    }

    /**
     * Execute a top-level defend action and its applicable active Gem Abilities.
     *
     * @param Character $character
     * @param ServerMonster $monster
     * @param bool $isPlayerVoided
     * @param int $characterHealth
     * @param int $monsterHealth
     * @return CharacterAttack
     */
    public function defend(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack
    {
        $this->defend->setIsRaidBoss($monster->isRaidBossMonster());
        $this->defend->setCharacterHealth($characterHealth);
        $this->defend->setMonsterHealth($monsterHealth);
        $this->defend->setCharacterAttackData($character, $isPlayerVoided);
        $this->defend->defend($character, $monster);

        $this->type = $this->defend;
        $this->executeGemAbilities($character, $monster, AttackType::DEFEND->value, $isPlayerVoided);

        return $this;
    }

    /**
     * Return the messages produced by the last selected action.
     *
     * @return mixed
     */
    public function getMessages()
    {
        return $this->type->getMessages();
    }

    /**
     * Clear messages from the last selected action.
     *
     * @return mixed
     */
    public function resetMessages()
    {
        $this->type->resetMessages();
    }

    /**
     * Return Character health after the last selected action.
     *
     * @return mixed
     */
    public function getCharacterHealth()
    {
        return $this->type->getCharacterHealth();
    }

    /**
     * Return Monster health after the last selected action.
     *
     * @return mixed
     */
    public function getMonsterHealth()
    {
        return $this->type->getMonsterHealth();
    }

    /**
     * Execute active Gem Abilities once for the selected top-level action and merge their result.
     *
     * @param Character $character
     * @param ServerMonster $monster
     * @param string $attackType
     * @param bool $isPlayerVoided
     * @return void
     */
    private function executeGemAbilities(Character $character, ServerMonster $monster, string $attackType, bool $isPlayerVoided): void
    {
        $cacheAttackType = $isPlayerVoided ? 'voided_'.$attackType : $attackType;
        $result = $this->gemAbilityExecutor->execute(
            $character,
            $cacheAttackType,
            $this->type->getCharacterHealth(),
            $this->type->getMonsterHealth(),
            $monster->isRaidBossMonster(),
        );

        $this->type->setMonsterHealth($result['monster_health']);
        $this->type->mergeMessages($result['messages']);
    }
}
