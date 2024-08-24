<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleMessages;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\AlchemistsRavenousDream;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\BloodyPuke;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\BookBindersFear;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DoubleAttack;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DoubleCast;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DoubleHeal;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\GunslingersAssassination;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\HammerSmash;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\HolySmite;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\MerchantSupply;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\PrisonerRage;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\SensualDance;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\ThiefBackStab;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\TripleAttack;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\VampireThirst;
use Exception;

class SpecialAttacks extends BattleMessages
{
    private int $characterHealth;

    private int $monsterHealth;

    private int $healFor = 0;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Set character health.
     *
     * @return $this
     */
    public function setCharacterHealth(int $characterHealth): SpecialAttacks
    {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    /**
     * Set monster health.
     *
     * @return $this
     */
    public function setMonsterHealth(int $monsterHealth): SpecialAttacks
    {
        $this->monsterHealth = $monsterHealth;

        return $this;
    }

    /**
     * Get Character health.
     */
    public function getCharacterHealth(): int
    {
        return $this->characterHealth;
    }

    /**
     * Get monster health.
     */
    public function getMonsterHealth(): int
    {
        return $this->monsterHealth;
    }

    /**
     * Get how much we should heal for.
     */
    public function getHealFor(): int
    {
        return $this->healFor;
    }

    /**
     * Do non caster based specials.
     *
     * @return void|null
     *
     * @throws Exception
     */
    public function doWeaponSpecials(Character $character, array $attackData, bool $isPvp = false)
    {
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

        if ($character->classType()->isAlcoholic()) {
            return $this->alcoholicsBloodyVomit($character, $attackData);
        }

        if ($character->classType()->isMerchant()) {
            return $this->merchantsSupply($character, $attackData);
        }

        if ($character->classType()->isVampire()) {
            return $this->vampireThirst($character, $attackData, $isPvp);
        }

        if ($character->classType()->isGunslinger()) {
            return $this->gunslingersAssassination($character, $attackData, $isPvp);
        }

        if ($character->classType()->isDancer()) {
            return $this->sensualDance($character, $attackData, $isPvp);
        }

        if ($character->classType()->isBookBinder()) {
            return $this->bookBindersFear($character, $attackData, $isPvp);
        }

        if ($character->classType()->isCleric()) {
            return $this->holySmite($character, $attackData, $isPvp);
        }

        return null;
    }

    /**
     * Do double cast spells
     *
     * @return void|null
     *
     * @throws Exception
     */
    public function doCastDamageSpecials(Character $character, array $attackData, bool $isPvp = false)
    {
        if ($character->classType()->isHeretic()) {
            $this->doubleCast($character, $attackData, $isPvp);
        }
    }

    /**
     * Do double healing.
     *
     * @throws Exception
     */
    public function doCastHealSpecials(Character $character, array $attackData, bool $isPvp = false): SpecialAttacks
    {
        if ($character->classType()->isProphet()) {
            $this->healFor += $this->doubleHeal($character, $attackData, $isPvp);
        }

        return $this;
    }

    /**
     * This attack can only be fired during pvp.
     *
     * This replaces the thieves shadow dance special for pvp.
     *
     * @return void
     */
    public function thiefBackStab(Character $character, array $attackData)
    {
        $thievesBackStab = resolve(ThiefBackStab::class);

        $thievesBackStab->setCharacterHealth($this->characterHealth);
        $thievesBackStab->setMonsterHealth($this->monsterHealth);
        $thievesBackStab->backstab($character, $attackData);

        $this->mergeMessages($thievesBackStab->getMessages());

        $this->characterHealth = $thievesBackStab->getCharacterHealth();
        $this->monsterHealth = $thievesBackStab->getMonsterHealth();

        $thievesBackStab->clearMessages();
    }

    /**
     * Do hammer smash attack.
     *
     * @return void
     */
    public function hammerSmash(Character $character, array $attackData, bool $isPvp = false)
    {
        $hammerSmash = resolve(HammerSmash::class);

        $hammerSmash->setCharacterHealth($this->characterHealth);
        $hammerSmash->setMonsterHealth($this->monsterHealth);
        $hammerSmash->handleHammerSmash($character, $attackData, $isPvp);

        if (! $isPvp) {
            $this->mergeMessages($hammerSmash->getMessages());
        } else {
            $this->mergeAttackerMessages($hammerSmash->getAttackerMessages());
            $this->mergeDefenderMessages($hammerSmash->getDefenderMessages());
        }

        $this->characterHealth = $hammerSmash->getCharacterHealth();
        $this->monsterHealth = $hammerSmash->getMonsterHealth();

        $hammerSmash->clearMessages();
    }

    /**
     * Do alchemists ravenous rage attack.
     *
     * @return void
     */
    public function alchemistsRavenousRage(Character $character, array $attackData, bool $isPvp = false)
    {
        $alchemistsRavenousDream = resolve(AlchemistsRavenousDream::class);

        $alchemistsRavenousDream->setCharacterHealth($this->characterHealth);
        $alchemistsRavenousDream->setMonsterHealth($this->monsterHealth);
        $alchemistsRavenousDream->handleAttack($character, $attackData, $isPvp);

        if (! $isPvp) {
            $this->mergeMessages($alchemistsRavenousDream->getMessages());
        } else {
            $this->mergeAttackerMessages($alchemistsRavenousDream->getAttackerMessages());
            $this->mergeDefenderMessages($alchemistsRavenousDream->getDefenderMessages());
        }

        $this->characterHealth = $alchemistsRavenousDream->getCharacterHealth();
        $this->monsterHealth = $alchemistsRavenousDream->getMonsterHealth();

        $alchemistsRavenousDream->clearMessages();
    }

    /**
     * Do tripple attack.
     *
     * @return void
     */
    public function tripleAttack(Character $character, array $attackData, $isPvp = false)
    {
        $tripleAttack = resolve(TripleAttack::class);

        $tripleAttack->setCharacterHealth($this->characterHealth);
        $tripleAttack->setMonsterHealth($this->monsterHealth);
        $tripleAttack->handleAttack($character, $attackData, $isPvp);

        if (! $isPvp) {
            $this->mergeMessages($tripleAttack->getMessages());
        } else {
            $this->mergeAttackerMessages($tripleAttack->getAttackerMessages());
            $this->mergeDefenderMessages($tripleAttack->getDefenderMessages());
        }

        $this->characterHealth = $tripleAttack->getCharacterHealth();
        $this->monsterHealth = $tripleAttack->getMonsterHealth();

        $tripleAttack->clearMessages();
    }

    /**
     * Double damage.
     *
     * @return void
     */
    public function doubleDamage(Character $character, array $attackData, bool $isPvp = false)
    {
        $doubleAttack = resolve(DoubleAttack::class);

        $doubleAttack->setCharacterHealth($this->characterHealth);
        $doubleAttack->setMonsterHealth($this->monsterHealth);
        $doubleAttack->handleAttack($character, $attackData, $isPvp);

        if (! $isPvp) {
            $this->mergeMessages($doubleAttack->getMessages());
        } else {
            $this->mergeAttackerMessages($doubleAttack->getAttackerMessages());
            $this->mergeDefenderMessages($doubleAttack->getDefenderMessages());
        }

        $this->characterHealth = $doubleAttack->getCharacterHealth();
        $this->monsterHealth = $doubleAttack->getMonsterHealth();

        $doubleAttack->clearMessages();
    }

    /**
     * Double cast.
     *
     * @return void
     */
    public function doubleCast(Character $character, array $attackData, bool $isPvp = false)
    {
        $doubleCast = resolve(DoubleCast::class);

        $doubleCast->setCharacterHealth($this->characterHealth);
        $doubleCast->setMonsterHealth($this->monsterHealth);
        $doubleCast->handleAttack($character, $attackData, $isPvp);

        if (! $isPvp) {
            $this->mergeMessages($doubleCast->getMessages());
        } else {
            $this->mergeAttackerMessages($doubleCast->getAttackerMessages());
            $this->mergeDefenderMessages($doubleCast->getDefenderMessages());
        }

        $this->characterHealth = $doubleCast->getCharacterHealth();
        $this->monsterHealth = $doubleCast->getMonsterHealth();

        $doubleCast->clearMessages();
    }

    /**
     * Double heal.
     *
     * @return void
     */
    public function doubleHeal(Character $character, array $attackData, bool $isPvp = false): int
    {
        $doubleCast = resolve(DoubleHeal::class);

        $doubleCast->setCharacterHealth($this->characterHealth);
        $healForAmount = $doubleCast->handleHeal($character, $attackData, $isPvp);

        if (! $isPvp) {
            $this->mergeMessages($doubleCast->getMessages());
        } else {
            $this->mergeAttackerMessages($doubleCast->getAttackerMessages());
        }

        $doubleCast->clearMessages();

        return $healForAmount;
    }

    /**
     * Vampire thirst attack.
     *
     * @return void
     */
    public function vampireThirst(Character $character, array $attackData, bool $isPvp = false)
    {
        $thirst = resolve(VampireThirst::class);

        $thirst->setCharacterHealth($this->characterHealth);
        $thirst->setMonsterHealth($this->monsterHealth);
        $thirst->handleAttack($character, $attackData, $isPvp);

        $this->mergeMessages($thirst->getMessages());

        $this->characterHealth = $thirst->getCharacterHealth();
        $this->monsterHealth = $thirst->getMonsterHealth();

        $thirst->clearMessages();
    }

    /**
     * Prisoners rage attack.
     *
     * @return void
     */
    public function prisonersRage(Character $character, array $attackData, bool $isPvp = false)
    {
        $prisonersRage = resolve(PrisonerRage::class);

        $prisonersRage->setCharacterHealth($this->characterHealth);
        $prisonersRage->setMonsterHealth($this->monsterHealth);
        $prisonersRage->handleAttack($character, $attackData, $isPvp);

        $this->mergeMessages($prisonersRage->getMessages());

        $this->characterHealth = $prisonersRage->getCharacterHealth();
        $this->monsterHealth = $prisonersRage->getMonsterHealth();

        $prisonersRage->clearMessages();
    }

    /**
     * Alcoholics bloody vomit
     *
     * @return void
     */
    public function alcoholicsBloodyVomit(Character $character, array $attackData, bool $isPvp = false)
    {
        $alcoholicsBloodyVomit = resolve(BloodyPuke::class);

        $alcoholicsBloodyVomit->setCharacterHealth($this->characterHealth);
        $alcoholicsBloodyVomit->setMonsterHealth($this->monsterHealth);
        $alcoholicsBloodyVomit->handleAttack($character, $attackData, $isPvp);

        $this->mergeMessages($alcoholicsBloodyVomit->getMessages());

        $this->characterHealth = $alcoholicsBloodyVomit->getCharacterHealth();
        $this->monsterHealth = $alcoholicsBloodyVomit->getMonsterHealth();

        $alcoholicsBloodyVomit->clearMessages();
    }

    /**
     * Merchants supply attack
     *
     * @return void
     */
    public function merchantsSupply(Character $character, array $attackData, bool $isPvp = false)
    {
        $merchantsSupply = resolve(MerchantSupply::class);

        $merchantsSupply->setCharacterHealth($this->characterHealth);
        $merchantsSupply->setMonsterHealth($this->monsterHealth);
        $merchantsSupply->handleAttack($character, $attackData, $isPvp);

        $this->mergeMessages($merchantsSupply->getMessages());

        $this->characterHealth = $merchantsSupply->getCharacterHealth();
        $this->monsterHealth = $merchantsSupply->getMonsterHealth();

        $merchantsSupply->clearMessages();
    }

    /**
     * Gunslingers Assassination special attack.
     *
     * @return void
     */
    public function gunslingersAssassination(Character $character, array $attackData, bool $isPvp = false)
    {
        $gunslingersAssassination = resolve(GunslingersAssassination::class);

        $gunslingersAssassination->setCharacterHealth($this->characterHealth);
        $gunslingersAssassination->setMonsterHealth($this->monsterHealth);
        $gunslingersAssassination->handleAttack($character, $attackData, $isPvp);

        $this->mergeMessages($gunslingersAssassination->getMessages());

        $this->characterHealth = $gunslingersAssassination->getCharacterHealth();
        $this->monsterHealth = $gunslingersAssassination->getMonsterHealth();

        $gunslingersAssassination->clearMessages();
    }

    /**
     * Dancers Sensual Dance special attack.
     *
     * @return void
     */
    public function sensualDance(Character $character, array $attackData, bool $isPvp = false)
    {
        $sensualDance = resolve(SensualDance::class);

        $sensualDance->setCharacterHealth($this->characterHealth);
        $sensualDance->setMonsterHealth($this->monsterHealth);
        $sensualDance->handleAttack($character, $attackData, $isPvp);

        $this->mergeMessages($sensualDance->getMessages());

        $this->characterHealth = $sensualDance->getCharacterHealth();
        $this->monsterHealth = $sensualDance->getMonsterHealth();

        $sensualDance->clearMessages();
    }

    /**
     * Book Binders Fear Special Attack
     *
     * @return void
     */
    public function bookBindersFear(Character $character, array $attackData, bool $isPvp = false)
    {
        $bookBindersFear = resolve(BookBindersFear::class);

        $bookBindersFear->setCharacterHealth($this->characterHealth);
        $bookBindersFear->setMonsterHealth($this->monsterHealth);
        $bookBindersFear->handleAttack($character, $attackData, $isPvp);

        $this->mergeMessages($bookBindersFear->getMessages());

        $this->characterHealth = $bookBindersFear->getCharacterHealth();
        $this->monsterHealth = $bookBindersFear->getMonsterHealth();

        $bookBindersFear->clearMessages();
    }

    /**
     * Book Binders Fear Special Attack
     *
     * @return void
     */
    public function holySmite(Character $character, array $attackData, bool $isPvp = false)
    {
        $holySmite = resolve(HolySmite::class);

        $holySmite->setCharacterHealth($this->characterHealth);
        $holySmite->setMonsterHealth($this->monsterHealth);
        $holySmite->handleAttack($character, $attackData, $isPvp);

        $this->mergeMessages($holySmite->getMessages());

        $this->characterHealth = $holySmite->getCharacterHealth();
        $this->monsterHealth = $holySmite->getMonsterHealth();

        $holySmite->clearMessages();
    }
}
