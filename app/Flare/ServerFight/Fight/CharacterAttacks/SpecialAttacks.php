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

    public function doWeaponSpecials(Character $character, array $attackData) {
        if ($character->classType()->isBlacksmith()) {
            return $this->hammerSmash($character, $attackData);
        }

        if ($character->classType()->isArcaneAlchemist()) {
            return $this->alchemistsRavenousRage($character, $attackData);
        }

        if ($character->classType()->isRanger()) {
            return $this->tripleAttack($character, $attackData);
        }

        if ($character->classType()->isFighter()) {
            return $this->doubleDamage($character, $attackData);
        }

        if ($character->classType()->isVampire()) {
            return $this->vampireThirst($character, $attackData);
        }
    }

    public function doCastDamageSpecials(Character $character, array $attackData) {
        if ($character->classType()->isHeretic()) {
            $this->doubleCast($character, $attackData);
        }

        if ($character->classType()->isVampire()) {
            return $this->vampireThirst($character, $attackData);
        }
    }

    public function doCastHealSpecials(Character $character, array $attackData) {
        if ($character->classType()->isProphet()) {
            $this->doubleHeal($character, $attackData);
        }

        if ($character->classType()->isVampire()) {
            return $this->vampireThirst($character, $attackData);
        }
    }

    public function hammerSmash(Character $character, array $attackData) {
        $hammerSmash = resolve(HammerSmash::class);

        $hammerSmash->setCharacterHealth($this->characterHealth);
        $hammerSmash->setMonsterHealth($this->monsterHealth);
        $hammerSmash->handleHammerSmash($character, $attackData);

        $this->mergeMessages($hammerSmash->getMessages());

        $this->characterHealth = $hammerSmash->getCharacterHealth();
        $this->monsterHealth   = $hammerSmash->getMonsterHealth();

        $hammerSmash->clearMessages();
    }

    public function alchemistsRavenousRage(Character $character, array $attackData) {
        $alchemistsRavenousDream = resolve(AlchemistsRavenousDream::class);

        $alchemistsRavenousDream->setCharacterHealth($this->characterHealth);
        $alchemistsRavenousDream->setMonsterHealth($this->monsterHealth);
        $alchemistsRavenousDream->handleAttack($character, $attackData);

        $this->mergeMessages($alchemistsRavenousDream->getMessages());

        $this->characterHealth = $alchemistsRavenousDream->getCharacterHealth();
        $this->monsterHealth   = $alchemistsRavenousDream->getMonsterHealth();

        $alchemistsRavenousDream->clearMessages();
    }

    public function tripleAttack(Character $character, array $attackData) {
        $tripleAttack = resolve(TripleAttack::class);

        $tripleAttack->setCharacterHealth($this->characterHealth);
        $tripleAttack->setMonsterHealth($this->monsterHealth);
        $tripleAttack->handleAttack($character, $attackData);

        $this->mergeMessages($tripleAttack->getMessages());

        $this->characterHealth = $tripleAttack->getCharacterHealth();
        $this->monsterHealth   = $tripleAttack->getMonsterHealth();

        $tripleAttack->clearMessages();
    }

    public function doubleDamage(Character $character, array $attackData) {
        $doubleAttack = resolve(DoubleAttack::class);

        $doubleAttack->setCharacterHealth($this->characterHealth);
        $doubleAttack->setMonsterHealth($this->monsterHealth);
        $doubleAttack->handleAttack($character, $attackData);

        $this->mergeMessages($doubleAttack->getMessages());

        $this->characterHealth = $doubleAttack->getCharacterHealth();
        $this->monsterHealth   = $doubleAttack->getMonsterHealth();

        $doubleAttack->clearMessages();
    }

    public function doubleCast(Character $character, array $attackData) {
        $doubleCast = resolve(DoubleCast::class);

        $doubleCast->setCharacterHealth($this->characterHealth);
        $doubleCast->setMonsterHealth($this->monsterHealth);
        $doubleCast->handleAttack($character, $attackData);

        $this->mergeMessages($doubleCast->getMessages());

        $this->characterHealth = $doubleCast->getCharacterHealth();
        $this->monsterHealth   = $doubleCast->getMonsterHealth();

        $doubleCast->clearMessages();
    }

    public function doubleHeal(Character $character, array $attackData) {
        $doubleCast = resolve(DoubleHeal::class);

        $doubleCast->setCharacterHealth($this->characterHealth);
        $doubleCast->handleHeal($character, $attackData);

        $this->mergeMessages($doubleCast->getMessages());

        $this->characterHealth = $doubleCast->getCharacterHealth();

        $doubleCast->clearMessages();
    }

    public function vampireThirst(Character $character, array $attackData) {
        $thirst = resolve(VampireThirst::class);

        $thirst->setCharacterHealth($this->characterHealth);
        $thirst->setMonsterHealth($this->monsterHealth);
        $thirst->handleAttack($character, $attackData);

        $this->mergeMessages($thirst->getMessages());

        $this->characterHealth = $thirst->getCharacterHealth();
        $this->monsterHealth   = $thirst->getMonsterHealth();

        $thirst->clearMessages();
    }

}
