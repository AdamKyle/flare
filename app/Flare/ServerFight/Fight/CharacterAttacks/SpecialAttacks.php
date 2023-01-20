<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\BattleMessages;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\AlchemistsRavenousDream;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DoubleAttack;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DoubleCast;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DoubleHeal;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\HammerSmash;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\PrisonerRage;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\ThiefBackStab;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\TripleAttack;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\VampireThirst;

class SpecialAttacks extends BattleMessages {

    private int $characterHealth;

    private int $monsterHealth;

    public function __construct() {
        parent::__construct();
    }

    public function setCharacterHealth(int $characterHealth): SpecialAttacks {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function setMonsterHealth(int $monsterHealth): SpecialAttacks {
        $this->monsterHealth = $monsterHealth;

        return $this;
    }

    public function getCharacterHealth(): int {
        return $this->characterHealth;
    }

    public function getMonsterHealth(): int {
        return $this->monsterHealth;
    }

    public function  doWeaponSpecials(Character $character, array $attackData, bool $isPvp = false) {
        if ($character->classType()->isBlacksmith()) {
            return $this->hammerSmash($character, $attackData, $isPvp);
        }

        if ($character->classType()->isArcaneAlchemist()) {
            return $this->alchemistsRavenousRage($character, $attackData, $isPvp);
        }

        if ($character->classType()->isRanger()) {
            return $this->tripleAttack($character, $attackData, $isPvp);
        }

        if ($character->classType()->isFighter()) {
            return $this->doubleDamage($character, $attackData, $isPvp);
        }

        if ($character->classType()->isVampire()) {
            return $this->vampireThirst($character, $attackData, $isPvp);
        }

        if ($character->classType()->isThief()) {
            return $this->thiefBackStab($character, $attackData);
        }

        if ($character->classType()->isPrisoner()) {
             return $this->prisonersRage($character, $attackData);
        }
    }

    public function doCastDamageSpecials(Character $character, array $attackData, bool $isPvp = false) {
        if ($character->classType()->isHeretic()) {
            $this->doubleCast($character, $attackData, $isPvp);
        }

        if ($character->classType()->isVampire()) {
            return $this->vampireThirst($character, $attackData, $isPvp);
        }
    }

    public function doCastHealSpecials(Character $character, array $attackData, bool $isPvp = false) {
        if ($character->classType()->isProphet()) {
            $this->doubleHeal($character, $attackData, $isPvp);
        }

        if ($character->classType()->isVampire()) {
            return $this->vampireThirst($character, $attackData, $isPvp);
        }
    }

    /**
     * This attack can only be fired during pvp.
     *
     * This replaces the thieves shadow dance special for pvp.
     *
     * @param Character $character
     * @param array $attackData
     * @return void
     */
    public function thiefBackStab(Character $character, array $attackData) {
        $thievesBackStab = resolve(ThiefBackStab::class);

        $thievesBackStab->setCharacterHealth($this->characterHealth);
        $thievesBackStab->setMonsterHealth($this->monsterHealth);
        $thievesBackStab->backstab($character, $attackData);

        $this->mergeMessages($thievesBackStab->getMessages());

        $this->characterHealth = $thievesBackStab->getCharacterHealth();
        $this->monsterHealth   = $thievesBackStab->getMonsterHealth();

        $thievesBackStab->clearMessages();
    }

    /**
     * Do hammer smash attack.
     *
     * @param Character $character
     * @param array $attackData
     * @param bool $isPvp
     * @return void
     */
    public function hammerSmash(Character $character, array $attackData, bool $isPvp = false) {
        $hammerSmash = resolve(HammerSmash::class);

        $hammerSmash->setCharacterHealth($this->characterHealth);
        $hammerSmash->setMonsterHealth($this->monsterHealth);
        $hammerSmash->handleHammerSmash($character, $attackData, $isPvp);

        if (!$isPvp) {
            $this->mergeMessages($hammerSmash->getMessages());
        } else {
            $this->mergeAttackerMessages($hammerSmash->getAttackerMessages());
            $this->mergeDefenderMessages($hammerSmash->getDefenderMessages());
        }

        $this->characterHealth = $hammerSmash->getCharacterHealth();
        $this->monsterHealth   = $hammerSmash->getMonsterHealth();

        $hammerSmash->clearMessages();
    }

    /**
     * Do alchemists ravenous rage attack.
     *
     * @param Character $character
     * @param array $attackData
     * @param bool $isPvp
     * @return void
     */
    public function alchemistsRavenousRage(Character $character, array $attackData, bool $isPvp = false) {
        $alchemistsRavenousDream = resolve(AlchemistsRavenousDream::class);

        $alchemistsRavenousDream->setCharacterHealth($this->characterHealth);
        $alchemistsRavenousDream->setMonsterHealth($this->monsterHealth);
        $alchemistsRavenousDream->handleAttack($character, $attackData, $isPvp);

        if (!$isPvp) {
            $this->mergeMessages($alchemistsRavenousDream->getMessages());
        } else {
            $this->mergeAttackerMessages($alchemistsRavenousDream->getAttackerMessages());
            $this->mergeDefenderMessages($alchemistsRavenousDream->getDefenderMessages());
        }

        $this->characterHealth = $alchemistsRavenousDream->getCharacterHealth();
        $this->monsterHealth   = $alchemistsRavenousDream->getMonsterHealth();

        $alchemistsRavenousDream->clearMessages();
    }

    /**
     * Do tripple attack.
     *
     * @param Character $character
     * @param array $attackData
     * @param $isPvp
     * @return void
     */
    public function tripleAttack(Character $character, array $attackData, $isPvp = false) {
        $tripleAttack = resolve(TripleAttack::class);

        $tripleAttack->setCharacterHealth($this->characterHealth);
        $tripleAttack->setMonsterHealth($this->monsterHealth);
        $tripleAttack->handleAttack($character, $attackData, $isPvp);

        if (!$isPvp) {
            $this->mergeMessages($tripleAttack->getMessages());
        } else {
            $this->mergeAttackerMessages($tripleAttack->getAttackerMessages());
            $this->mergeDefenderMessages($tripleAttack->getDefenderMessages());
        }

        $this->characterHealth = $tripleAttack->getCharacterHealth();
        $this->monsterHealth   = $tripleAttack->getMonsterHealth();

        $tripleAttack->clearMessages();
    }

    /**
     * Double damage.
     *
     * @param Character $character
     * @param array $attackData
     * @param bool $isPvp
     * @return void
     */
    public function doubleDamage(Character $character, array $attackData, bool $isPvp = false) {
        $doubleAttack = resolve(DoubleAttack::class);

        $doubleAttack->setCharacterHealth($this->characterHealth);
        $doubleAttack->setMonsterHealth($this->monsterHealth);
        $doubleAttack->handleAttack($character, $attackData, $isPvp);

        if (!$isPvp) {
            $this->mergeMessages($doubleAttack->getMessages());
        } else {
            $this->mergeAttackerMessages($doubleAttack->getAttackerMessages());
            $this->mergeDefenderMessages($doubleAttack->getDefenderMessages());
        }

        $this->characterHealth = $doubleAttack->getCharacterHealth();
        $this->monsterHealth   = $doubleAttack->getMonsterHealth();

        $doubleAttack->clearMessages();
    }

    /**
     * Double cast.
     *
     * @param Character $character
     * @param array $attackData
     * @param bool $isPvp
     * @return void
     */
    public function doubleCast(Character $character, array $attackData, bool $isPvp = false) {
        $doubleCast = resolve(DoubleCast::class);

        $doubleCast->setCharacterHealth($this->characterHealth);
        $doubleCast->setMonsterHealth($this->monsterHealth);
        $doubleCast->handleAttack($character, $attackData, $isPvp);

        if (!$isPvp) {
            $this->mergeMessages($doubleCast->getMessages());
        } else {
            $this->mergeAttackerMessages($doubleCast->getAttackerMessages());
            $this->mergeDefenderMessages($doubleCast->getDefenderMessages());
        }

        $this->characterHealth = $doubleCast->getCharacterHealth();
        $this->monsterHealth   = $doubleCast->getMonsterHealth();

        $doubleCast->clearMessages();
    }

    /**
     * Double heal.
     *
     * @param Character $character
     * @param array $attackData
     * @param bool $isPvp
     * @return void
     */
    public function doubleHeal(Character $character, array $attackData, bool $isPvp = false) {
        $doubleCast = resolve(DoubleHeal::class);

        $doubleCast->setCharacterHealth($this->characterHealth);
        $doubleCast->handleHeal($character, $attackData, $isPvp);

        if (!$isPvp) {
            $this->mergeMessages($doubleCast->getMessages());
        } else {
            $this->mergeAttackerMessages($doubleCast->getAttackerMessages());
        }

        $this->characterHealth = $doubleCast->getCharacterHealth();

        $doubleCast->clearMessages();
    }

    /**
     * Vampire thirst attack.
     *
     * @param Character $character
     * @param array $attackData
     * @param bool $isPvp
     * @return void
     */
    public function vampireThirst(Character $character, array $attackData, bool $isPvp = false) {
        $thirst = resolve(VampireThirst::class);

        $thirst->setCharacterHealth($this->characterHealth);
        $thirst->setMonsterHealth($this->monsterHealth);
        $thirst->handleAttack($character, $attackData, $isPvp);

        $this->mergeMessages($thirst->getMessages());

        $this->characterHealth = $thirst->getCharacterHealth();
        $this->monsterHealth   = $thirst->getMonsterHealth();

        $thirst->clearMessages();
    }

    /**
     * Prisoners rage attack.
     *
     * @param Character $character
     * @param array $attackData
     * @param bool $isPvp
     * @return void
     */
    public function prisonersRage(Character $character, array $attackData, bool $isPvp = false) {
        $prisonersRage = resolve(PrisonerRage::class);

        $prisonersRage->setCharacterHealth($this->characterHealth);
        $prisonersRage->setMonsterHealth($this->monsterHealth);
        $prisonersRage->handleAttack($character, $attackData, $isPvp);

        $this->mergeMessages($prisonersRage->getMessages());

        $this->characterHealth = $prisonersRage->getCharacterHealth();
        $this->monsterHealth   = $prisonersRage->getMonsterHealth();

        $prisonersRage->clearMessages();
    }

}
