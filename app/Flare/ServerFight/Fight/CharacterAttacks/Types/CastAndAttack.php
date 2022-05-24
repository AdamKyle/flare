<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Affixes;
use App\Flare\ServerFight\Fight\CanHit;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;

class CastAndAttack extends BattleBase
{

    private int $monsterHealth;

    private int $characterHealth;

    private array $attackData;

    private bool $isVoided;

    private CharacterCacheData $characterCacheData;

    private Entrance $entrance;

    private CanHit $canHit;

    private Affixes $affixes;

    private WeaponType $weaponType;

    private CastType $castType;

    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit, Affixes $affixes, WeaponType $weaponType, CastType $castType)
    {
        parent::__construct();

        $this->characterCacheData = $characterCacheData;
        $this->entrance           = $entrance;
        $this->canHit             = $canHit;
        $this->affixes            = $affixes;
        $this->weaponType         = $weaponType;
        $this->castType           = $castType;
    }

    public function setMonsterHealth(int $monsterHealth): CastAndAttack
    {
        $this->monsterHealth = $monsterHealth;

        return $this;
    }

    public function setCharacterHealth(int $characterHealth): CastAndAttack
    {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function setCharacterAttackData(Character $character, bool $isVoided): CastAndAttack
    {

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $isVoided ? 'voided_cast_and_attack' : 'cast_and_attack');
        $this->isVoided = $isVoided;

        return $this;
    }

    public function resetMessages()
    {
        $this->clearMessages();
        $this->entrance->clearMessages();
    }

    public function getMonsterHealth()
    {
        return $this->monsterHealth;
    }

    public function getCharacterHealth()
    {
        return $this->characterHealth;
    }

    public function handleAttack(Character $character, ServerMonster $monster) {
        $this->entrance->playerEntrance($character, $monster, $this->attackData);

        $this->mergeMessages($this->entrance->getMessages());

        if ($this->entrance->isEnemyEntranced()) {
            $this->handleCastAttack($character, $monster);
            $this->handleWeaponAttack($character, $monster);
            $this->secondaryAttack($character, $monster);

            return $this;
        }

        if ($this->canHit->canPlayerAutoHit($character)) {
            $this->handleCastAttack($character, $monster);
            $this->handleWeaponAttack($character, $monster);
            $this->secondaryAttack($character, $monster);

            return $this;
        }

        $this->castAttack($character, $monster);
        $this->weaponAttack($character, $monster);

        return $this;
    }

    protected function weaponAttack(Character $character, ServerMonster $monster) {
        if ($this->canHit->canPlayerHitMonster($character, $monster, $this->isVoided)) {

            $weaponDamage = $this->attackData['weapon_damage'];

            if ($monster->getMonsterStat('ac') > $weaponDamage) {
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

    protected function castAttack(Character $character, ServerMonster $monster) {
        if ($this->canHit->canPlayerCastSpell($character, $monster, $this->isVoided)) {

            $spellDamage = $this->attackData['spell_damage'];

            if ($monster->getMonsterStat('ac') > $spellDamage) {
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

    protected function secondaryAttack(Character $character, ServerMonster $monster) {
        if (!$this->isVoided) {
            $this->affixLifeStealingDamage($character, $monster);
            $this->affixDamage($character, $monster);
            $this->ringDamage();
        } else {
            $this->addMessage('You are voided, none of your rings or enchantments fire ...', 'enemy-action');
        }
    }

    protected function affixDamage(Character $character, ServerMonster $monster) {
        $damage = $this->affixes->getCharacterAffixDamage($character, $monster, $this->attackData);

        if ($damage > 0) {
            $this->monsterHealth -= $damage;
        }

        $this->mergeMessages($this->affixes->getMessages());

        $this->affixes->clearMessages();
    }

    protected function affixLifeStealingDamage(Character $character, ServerMonster $monster) {
        $damage = $this->affixes->getAffixLifeSteal($character, $monster, $this->attackData);

        if ($damage > 0) {
            $this->monsterHealth   -= $damage;
            $this->characterHealth += $damage;

            $maxCharacterHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');

            if ($this->characterHealth >= $maxCharacterHealth) {
                $this->characterHealth = $maxCharacterHealth;
            }
        }

        $this->mergeMessages($this->affixes->getMessages());

        $this->affixes->clearMessages();
    }

    protected function ringDamage() {
        $ringDamage = $this->attackData['ring_damage'];

        if ($ringDamage > 0) {
            $this->monsterHealth -= ($ringDamage - $ringDamage * $this->attackData['damage_deduction']);

            $this->addMessage('Your rings hit for: ' . number_format($ringDamage), 'player-action');
        }
    }

    protected function handleWeaponAttack(Character $character, ServerMonster $monster) {
        $weaponDamage = $this->attackData['weapon_damage'];

        $this->weaponType->setMonsterHealth($this->monsterHealth)
                         ->setCharacterHealth($this->characterHealth)
                         ->setCharacterAttackData($character, $this->isVoided)
                         ->weaponAttack($character, $monster, $weaponDamage);

        $this->mergeMessages($this->weaponType->getMessages());

        $this->characterHealth = $this->weaponType->getCharacterHealth();
        $this->monsterHealth   = $this->weaponType->getMonsterHealth();

        $this->weaponType->resetMessages();
    }

    protected function handleCastAttack(Character $character, ServerMonster $monster) {
        $spellDamage = $this->attackData['spell_damage'];

        $this->castType->setMonsterHealth($this->monsterHealth)
                       ->setCharacterHealth($this->characterHealth)
                       ->setCharacterAttackData($character, $this->isVoided)
                       ->doSpellDamage($character, $monster, $spellDamage);

        $this->mergeMessages($this->weaponType->getMessages());

        $this->characterHealth = $this->weaponType->getCharacterHealth();
        $this->monsterHealth   = $this->weaponType->getMonsterHealth();

        $this->weaponType->resetMessages();
    }
}
