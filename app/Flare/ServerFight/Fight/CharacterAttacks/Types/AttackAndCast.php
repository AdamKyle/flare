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

    protected array $attackData;

    protected bool $isVoided;

    private Entrance $entrance;

    private CanHit $canHit;

    private SecondaryAttacks $secondaryAttacks;

    private WeaponType $weaponType;

    private CastType $castType;

    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit, SecondaryAttacks $secondaryAttacks, WeaponType $weaponType, CastType $castType)
    {
        parent::__construct($characterCacheData);

        $this->entrance           = $entrance;
        $this->canHit             = $canHit;
        $this->secondaryAttacks   = $secondaryAttacks;
        $this->weaponType         = $weaponType;
        $this->castType           = $castType;
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
        $this->entrance->attackerEntrancesDefender($attacker, $this->attackData, $this->isVoided);

        $this->mergeAttackerMessages($this->entrance->getAttackerMessages());
        $this->mergeDefenderMessages($this->entrance->getDefenderMessages());

        if ($this->entrance->isEnemyEntranced()) {
            $this->handlePvpWeaponAttack($attacker);
            $this->handlePvpCastAttack($attacker, $defender);
            $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);

            return $this;
        }

        $this->doPvpWeaponAttack($attacker, $defender);
        $this->doPvpCastAttack($attacker, $defender);
        $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);

        return $this;
    }

    public function handleAttack(Character $character, ServerMonster $monster) {
        $this->entrance->playerEntrance($character, $monster, $this->attackData);

        $this->mergeMessages($this->entrance->getMessages());

        if ($this->entrance->isEnemyEntranced()) {
            $this->handleWeaponAttack($character, $monster);
            $this->handleCastAttack($character, $monster);
            $this->secondaryAttack($character, $monster);

            return $this;
        }

        if ($this->canHit->canPlayerAutoHit($character)) {
            $this->handleWeaponAttack($character, $monster);
            $this->handleCastAttack($character, $monster);
            $this->secondaryAttack($character, $monster);

            return $this;
        }

        $this->weaponAttack($character, $monster);
        $this->castAttack($character, $monster);

        return $this;
    }

    protected function weaponAttack(Character $character, ServerMonster $monster) {
        if ($this->canHit->canPlayerHitMonster($character, $monster, $this->isVoided) || $this->entrance->isEnemyEntranced()) {

            $weaponDamage = $this->attackData['weapon_damage'];

            if ($monster->getMonsterStat('ac') > $weaponDamage && !$this->entrance->isEnemyEntranced()) {
                $this->addMessage('Your weapon was blocked!', 'enemy-action');
            } else {
                $this->handleWeaponAttack($character, $monster);

                $this->secondaryAttack($character, $monster);
            }
        } else {
            $this->addMessage('Your attack missed!', 'enemy-action');

            $this->secondaryAttack($character, $monster);
        }
    }

    protected function doPvpCastAttack(Character $attacker, Character $defender) {

        $spellDamage = $this->attackData['spell_damage'];

        if ($this->canHit->canPlayerCastSpellOnPlayer($attacker, $defender, $this->isVoided) || $this->entrance->isEnemyEntranced()) {
            if ($this->characterCacheData->getCachedCharacterData($defender, 'ac') > $spellDamage && !$this->entrance->isEnemyEntranced()) {
                $this->addAttackerMessage('Your spell was blocked!', 'enemy-action');

                return $this;
            } else {
                $this->handlePvpCastAttack($attacker, $defender);

                $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);
            }
        } else {
            $this->addAttackerMessage('Your spell fizzled and failed!', 'enemy-action');

            $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);
        }

        return $this;
    }

    protected function castAttack(Character $character, ServerMonster $monster) {
        if ($this->canHit->canPlayerCastSpell($character, $monster, $this->isVoided) || $this->entrance->isEnemyEntranced()) {

            $spellDamage = $this->attackData['spell_damage'];

            if ($monster->getMonsterStat('ac') > $spellDamage && !$this->entrance->isEnemyEntranced()) {
                $this->addMessage('Your weapon was blocked!', 'enemy-action');
            } else {
                $this->handleCastAttack($character, $monster);

                $this->secondaryAttack($character, $monster);
            }
        } else {
            $this->addMessage('Your attack missed!', 'enemy-action');

            $this->secondaryAttack($character, $monster);
        }
    }

    protected function secondaryAttack(Character $character, ServerMonster $monster = null, float $affixReduction = 0.0, bool $isPvp = false) {
        if (!$this->isVoided) {

            $this->secondaryAttacks->setMonsterHealth($this->monsterHealth);
            $this->secondaryAttacks->setCharacterHealth($this->characterHealth);
            $this->secondaryAttacks->setAttackData($this->attackData);


            $this->secondaryAttacks->affixLifeStealingDamage($character, $monster, $affixReduction, $isPvp);
            $this->secondaryAttacks->affixDamage($character, $monster, $affixReduction, $isPvp);
            $this->secondaryAttacks->ringDamage($isPvp);

            if ($isPvp) {
                $this->mergeAttackerMessages($this->secondaryAttacks->getAttackerMessages());
                $this->mergeDefenderMessages($this->secondaryAttacks->getDefenderMessages());
            } else {
                $this->secondaryAttacks->mergeMessages($this->secondaryAttacks->getMessages());
            }

            $this->secondaryAttacks->clearMessages();

        } else {
            if ($isPvp) {
                $this->addAttackerMessage('You are voided, none of your rings or enchantments fire ...', 'enemy-action');
            } else {
                $this->addMessage('You are voided, none of your rings or enchantments fire ...', 'enemy-action');
            }
        }
    }

    protected function doPvpWeaponAttack(Character $attacker, Character $defender) {

        if ($this->canHit->canPlayerHitPlayer($attacker, $defender, $this->isVoided) || $this->entrance->isEnemyEntranced()) {
            $weaponDamage = $this->attackData['weapon_damage'];

            if ($this->characterCacheData->getCachedCharacterData($defender, 'ac') > $weaponDamage && !$this->entrance->isEnemyEntranced()) {
                $this->addAttackerMessage('Your attack was blocked', 'enemy-action');
                $this->addDefenderMessage('You managed to block the enemies attack', 'player-action');
                return $this;
            }

            $this->handlePvpWeaponAttack($attacker);
            $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($attacker, 'affix_damage_reduction'));
        } else {
            $this->addAttackerMessage('Your attack missed!', 'enemy-action');
        }

        return $this;
    }

    protected function handlePvpWeaponAttack(Character $attacker) {
        $weaponDamage = $this->attackData['weapon_damage'];

        $this->weaponType->setMonsterHealth($this->monsterHealth);
        $this->weaponType->setCharacterHealth($this->characterHealth);
        $this->weaponType->setCharacterAttackData($attacker, $this->isVoided);
        $this->weaponType->pvpWeaponDamage($attacker, $weaponDamage);

        $this->characterHealth = $this->weaponType->getCharacterHealth();
        $this->monsterHealth   = $this->weaponType->getMonsterHealth();

        $this->mergeAttackerMessages($this->weaponType->getAttackerMessages());
        $this->mergeDefenderMessages($this->weaponType->getDefenderMessages());

        $this->weaponType->resetMessages();
    }

    protected function handleWeaponAttack(Character $character, ServerMonster $monster) {
        $weaponDamage = $this->attackData['weapon_damage'];

        $this->weaponType->setMonsterHealth($this->monsterHealth);
        $this->weaponType->setCharacterHealth($this->characterHealth);
        $this->weaponType->setCharacterAttackData($character, $this->isVoided);
        $this->weaponType->weaponDamage($character, $monster->getName(), $weaponDamage);

        $this->mergeMessages($this->weaponType->getMessages());

        $this->characterHealth = $this->weaponType->getCharacterHealth();
        $this->monsterHealth   = $this->weaponType->getMonsterHealth();

        $this->weaponType->resetMessages();
    }

    protected function handlePvpCastAttack(Character $attacker, Character $defender) {
        $spellDamage = $this->attackData['spell_damage'];

        $this->castType->setMonsterHealth($this->monsterHealth);
        $this->castType->setCharacterHealth($this->characterHealth);
        $this->castType->setCharacterAttackData($attacker, $this->isVoided);
        $this->castType->pvpSpellDamage($attacker, $defender, $spellDamage, $this->entrance->isEnemyEntranced());

        $this->mergeMessages($this->castType->getMessages());

        $this->characterHealth = $this->castType->getCharacterHealth();
        $this->monsterHealth   = $this->castType->getMonsterHealth();

        $this->mergeAttackerMessages($this->castType->getAttackerMessages());
        $this->mergeDefenderMessages($this->castType->getDefenderMessages());

        $this->castType->resetMessages();
    }

    protected function handleCastAttack(Character $character, ServerMonster $monster) {
        $spellDamage = $this->attackData['spell_damage'];

        $this->castType->setMonsterHealth($this->monsterHealth);
        $this->castType->setCharacterHealth($this->characterHealth);
        $this->castType->setCharacterAttackData($character, $this->isVoided);
        $this->castType->spellDamage($character, $monster, $spellDamage, $this->entrance->isEnemyEntranced());

        $this->mergeMessages($this->castType->getMessages());

        $this->characterHealth = $this->castType->getCharacterHealth();
        $this->monsterHealth   = $this->castType->getMonsterHealth();

        $this->castType->resetMessages();
    }
}
