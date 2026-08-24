<?php

namespace App\Game\Battle\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\BattleMessages;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Values\SpecialAttackHandlerType;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Character\CharacterAttack\Values\ClassSpecialAttackType;
use Exception;

class SpecialAttacks extends BattleMessages
{
    private int $characterHealth;

    private int $monsterHealth;

    private int $healFor = 0;

    private bool $isRaidBoss = false;

    public function __construct(
        private readonly CharacterCacheData $characterCacheData,
        private readonly SpecialAttackRegistry $specialAttackRegistry,
    ) {
        parent::__construct();
    }

    /**
     * set is raid boss
     */
    public function setIsRaidBoss(bool $isRaidBoss): SpecialAttacks
    {
        $this->isRaidBoss = $isRaidBoss;

        return $this;
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
    public function doWeaponSpecials(Character $character, array $attackData)
    {
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

        if ($character->classType()->isPrisoner()) {
            return $this->prisonersRage($character, $attackData);
        }

        if ($character->classType()->isAlcoholic()) {
            return $this->alcoholicsBloodyVomit($character, $attackData);
        }

        if ($character->classType()->isMerchant()) {
            return $this->merchantsSupply($character, $attackData);
        }

        if ($character->classType()->isGunslinger()) {
            return $this->gunslingersAssassination($character, $attackData);
        }

        if ($character->classType()->isDancer()) {
            return $this->sensualDance($character, $attackData);
        }

        if ($character->classType()->isBookBinder()) {
            return $this->bookBindersFear($character, $attackData);
        }

        if ($character->classType()->isCleric()) {
            return $this->holySmite($character, $attackData);
        }

        if ($character->classType()->isApothecary()) {
            return $this->plagueSurge($character, $attackData);
        }

        if ($character->classType()->isBuccaneer()) {
            $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

            if (isset($extraActionData['type']) && $extraActionData['type'] === ClassSpecialAttackType::BUCCANEERS_DUAL_GUN_BARRAGE->value) {
                return $this->buccaneersDualGunBarrage($character, $attackData);
            }

            return $this->buccaneersBarrage($character, $attackData);
        }

        if ($character->classType()->isBeastmaster()) {
            $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

            if (isset($extraActionData['type']) && $extraActionData['type'] === ClassSpecialAttackType::BEAST_STOMP->value) {
                return $this->beastStomp($character, $attackData);
            }

            return $this->devilsPiercingShot($character, $attackData);
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
    public function doCastDamageSpecials(Character $character, array $attackData)
    {
        if ($character->classType()->isHeretic()) {
            $this->doubleCast($character, $attackData);
        }
    }

    /**
     * Do double healing.
     *
     * @throws Exception
     */
    public function doCastHealSpecials(Character $character, array $attackData): SpecialAttacks
    {
        if ($character->classType()->isProphet()) {
            $this->healFor += $this->doubleHeal($character, $attackData);
        }

        return $this;
    }

    /**
     * Do hammer smash attack.
     *
     * @return void
     */
    public function hammerSmash(Character $character, array $attackData)
    {
        $hammerSmash = $this->specialAttackRegistry->get(SpecialAttackHandlerType::HAMMER_SMASH);

        $hammerSmash->setIsRaidBoss($this->isRaidBoss);
        $hammerSmash->setCharacterHealth($this->characterHealth);
        $hammerSmash->setMonsterHealth($this->monsterHealth);
        $hammerSmash->handleHammerSmash($character, $attackData);

        $this->mergeMessages($hammerSmash->getMessages());

        $this->characterHealth = $hammerSmash->getCharacterHealth();
        $this->monsterHealth = $hammerSmash->getMonsterHealth();

        $hammerSmash->clearMessages();
    }

    /**
     * Do alchemists ravenous rage attack.
     *
     * @return void
     */
    public function alchemistsRavenousRage(Character $character, array $attackData)
    {
        $alchemistsRavenousDream = $this->specialAttackRegistry->get(SpecialAttackHandlerType::ALCHEMISTS_RAVENOUS_DREAM);

        $alchemistsRavenousDream->setIsRaidBoss($this->isRaidBoss);
        $alchemistsRavenousDream->setCharacterHealth($this->characterHealth);
        $alchemistsRavenousDream->setMonsterHealth($this->monsterHealth);
        $alchemistsRavenousDream->handleAttack($character, $attackData);

        $this->mergeMessages($alchemistsRavenousDream->getMessages());

        $this->characterHealth = $alchemistsRavenousDream->getCharacterHealth();
        $this->monsterHealth = $alchemistsRavenousDream->getMonsterHealth();

        $alchemistsRavenousDream->clearMessages();
    }

    /**
     * Do tripple attack.
     *
     * @return void
     */
    public function tripleAttack(Character $character, array $attackData)
    {
        $tripleAttack = $this->specialAttackRegistry->get(SpecialAttackHandlerType::TRIPLE_ATTACK);

        $tripleAttack->setIsRaidBoss($this->isRaidBoss);
        $tripleAttack->setCharacterHealth($this->characterHealth);
        $tripleAttack->setMonsterHealth($this->monsterHealth);
        $tripleAttack->handleAttack($character, $attackData);

        $this->mergeMessages($tripleAttack->getMessages());

        $this->characterHealth = $tripleAttack->getCharacterHealth();
        $this->monsterHealth = $tripleAttack->getMonsterHealth();

        $tripleAttack->clearMessages();
    }

    /**
     * Double damage.
     *
     * @return void
     */
    public function doubleDamage(Character $character, array $attackData)
    {
        $doubleAttack = $this->specialAttackRegistry->get(SpecialAttackHandlerType::DOUBLE_ATTACK);

        $doubleAttack->setIsRaidBoss($this->isRaidBoss);
        $doubleAttack->setCharacterHealth($this->characterHealth);
        $doubleAttack->setMonsterHealth($this->monsterHealth);
        $doubleAttack->handleAttack($character, $attackData);

        $this->mergeMessages($doubleAttack->getMessages());

        $this->characterHealth = $doubleAttack->getCharacterHealth();
        $this->monsterHealth = $doubleAttack->getMonsterHealth();

        $doubleAttack->clearMessages();
    }

    /**
     * Double cast.
     *
     * @return void
     */
    public function doubleCast(Character $character, array $attackData)
    {
        $doubleCast = $this->specialAttackRegistry->get(SpecialAttackHandlerType::DOUBLE_CAST);

        $doubleCast->setIsRaidBoss($this->isRaidBoss);
        $doubleCast->setCharacterHealth($this->characterHealth);
        $doubleCast->setMonsterHealth($this->monsterHealth);
        $doubleCast->handleAttack($character, $attackData);

        $this->mergeMessages($doubleCast->getMessages());

        $this->characterHealth = $doubleCast->getCharacterHealth();
        $this->monsterHealth = $doubleCast->getMonsterHealth();

        $doubleCast->clearMessages();
    }

    /**
     * Double heal.
     */
    public function doubleHeal(Character $character, array $attackData): int
    {
        $doubleCast = $this->specialAttackRegistry->get(SpecialAttackHandlerType::DOUBLE_HEAL);

        $doubleCast->setCharacterHealth($this->characterHealth);
        $healForAmount = $doubleCast->handleHeal($character, $attackData);

        $this->mergeMessages($doubleCast->getMessages());

        $doubleCast->clearMessages();

        return $healForAmount;
    }

    /**
     * Vampire thirst attack.
     *
     * @return void
     */
    public function vampireThirst(Character $character, array $attackData)
    {
        $thirst = $this->specialAttackRegistry->get(SpecialAttackHandlerType::VAMPIRE_THIRST);

        $thirst->setIsRaidBoss($this->isRaidBoss);
        $thirst->setCharacterHealth($this->characterHealth);
        $thirst->setMonsterHealth($this->monsterHealth);
        $thirst->handleAttack($character, $attackData);

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
    public function prisonersRage(Character $character, array $attackData)
    {
        $prisonersRage = $this->specialAttackRegistry->get(SpecialAttackHandlerType::PRISONER_RAGE);

        $prisonersRage->setIsRaidBoss($this->isRaidBoss);
        $prisonersRage->setCharacterHealth($this->characterHealth);
        $prisonersRage->setMonsterHealth($this->monsterHealth);
        $prisonersRage->handleAttack($character, $attackData);

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
    public function alcoholicsBloodyVomit(Character $character, array $attackData)
    {
        $alcoholicsBloodyVomit = $this->specialAttackRegistry->get(SpecialAttackHandlerType::BLOODY_PUKE);

        $alcoholicsBloodyVomit->setIsRaidBoss($this->isRaidBoss);
        $alcoholicsBloodyVomit->setCharacterHealth($this->characterHealth);
        $alcoholicsBloodyVomit->setMonsterHealth($this->monsterHealth);
        $alcoholicsBloodyVomit->handleAttack($character, $attackData);

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
    public function merchantsSupply(Character $character, array $attackData)
    {
        $merchantsSupply = $this->specialAttackRegistry->get(SpecialAttackHandlerType::MERCHANT_SUPPLY);

        $merchantsSupply->setIsRaidBoss($this->isRaidBoss);
        $merchantsSupply->setCharacterHealth($this->characterHealth);
        $merchantsSupply->setMonsterHealth($this->monsterHealth);
        $merchantsSupply->handleAttack($character, $attackData);

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
    public function gunslingersAssassination(Character $character, array $attackData)
    {
        $gunslingersAssassination = $this->specialAttackRegistry->get(SpecialAttackHandlerType::GUNSLINGERS_ASSASSINATION);

        $gunslingersAssassination->setIsRaidBoss($this->isRaidBoss);
        $gunslingersAssassination->setCharacterHealth($this->characterHealth);
        $gunslingersAssassination->setMonsterHealth($this->monsterHealth);
        $gunslingersAssassination->handleAttack($character, $attackData);

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
    public function sensualDance(Character $character, array $attackData)
    {
        $sensualDance = $this->specialAttackRegistry->get(SpecialAttackHandlerType::SENSUAL_DANCE);

        $sensualDance->setIsRaidBoss($this->isRaidBoss);
        $sensualDance->setCharacterHealth($this->characterHealth);
        $sensualDance->setMonsterHealth($this->monsterHealth);
        $sensualDance->handleAttack($character, $attackData);

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
    public function bookBindersFear(Character $character, array $attackData)
    {
        $bookBindersFear = $this->specialAttackRegistry->get(SpecialAttackHandlerType::BOOK_BINDERS_FEAR);

        $bookBindersFear->setIsRaidBoss($this->isRaidBoss);
        $bookBindersFear->setCharacterHealth($this->characterHealth);
        $bookBindersFear->setMonsterHealth($this->monsterHealth);
        $bookBindersFear->handleAttack($character, $attackData);

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
    public function holySmite(Character $character, array $attackData)
    {
        $holySmite = $this->specialAttackRegistry->get(SpecialAttackHandlerType::HOLY_SMITE);

        $holySmite->setIsRaidBoss($this->isRaidBoss);
        $holySmite->setCharacterHealth($this->characterHealth);
        $holySmite->setMonsterHealth($this->monsterHealth);
        $holySmite->handleAttack($character, $attackData);

        $this->mergeMessages($holySmite->getMessages());

        $this->characterHealth = $holySmite->getCharacterHealth();
        $this->monsterHealth = $holySmite->getMonsterHealth();

        $holySmite->clearMessages();
    }

    public function plagueSurge(Character $character, array $attackData)
    {
        $plagueSurge = $this->specialAttackRegistry->get(SpecialAttackHandlerType::PLAGUE_SURGE);

        $plagueSurge->setIsRaidBoss($this->isRaidBoss);
        $plagueSurge->setCharacterHealth($this->characterHealth);
        $plagueSurge->setMonsterHealth($this->monsterHealth);
        $plagueSurge->handleAttack($character, $attackData);

        $this->mergeMessages($plagueSurge->getMessages());

        $this->characterHealth = $plagueSurge->getCharacterHealth();
        $this->monsterHealth = $plagueSurge->getMonsterHealth();

        $plagueSurge->clearMessages();
    }

    /**
     * Buccaneer's Barrage special attack.
     *
     * @return void
     */
    public function buccaneersBarrage(Character $character, array $attackData)
    {
        $buccaneersBarrage = $this->specialAttackRegistry->get(SpecialAttackHandlerType::BUCCANEERS_BARRAGE);

        $buccaneersBarrage->setIsRaidBoss($this->isRaidBoss);
        $buccaneersBarrage->setCharacterHealth($this->characterHealth);
        $buccaneersBarrage->setMonsterHealth($this->monsterHealth);
        $buccaneersBarrage->handleAttack($character, $attackData);

        $this->mergeMessages($buccaneersBarrage->getMessages());

        $this->characterHealth = $buccaneersBarrage->getCharacterHealth();
        $this->monsterHealth = $buccaneersBarrage->getMonsterHealth();

        $buccaneersBarrage->clearMessages();
    }

    public function devilsPiercingShot(Character $character, array $attackData)
    {
        $devilsPiercingShot = $this->specialAttackRegistry->get(SpecialAttackHandlerType::DEVILS_PIERCING_SHOT);

        $devilsPiercingShot->setIsRaidBoss($this->isRaidBoss);
        $devilsPiercingShot->setCharacterHealth($this->characterHealth);
        $devilsPiercingShot->setMonsterHealth($this->monsterHealth);
        $devilsPiercingShot->handleAttack($character, $attackData);

        $this->mergeMessages($devilsPiercingShot->getMessages());

        $this->characterHealth = $devilsPiercingShot->getCharacterHealth();
        $this->monsterHealth = $devilsPiercingShot->getMonsterHealth();

        $devilsPiercingShot->clearMessages();
    }

    public function beastStomp(Character $character, array $attackData)
    {
        $beastStomp = $this->specialAttackRegistry->get(SpecialAttackHandlerType::BEAST_STOMP);

        $beastStomp->setIsRaidBoss($this->isRaidBoss);
        $beastStomp->setCharacterHealth($this->characterHealth);
        $beastStomp->setMonsterHealth($this->monsterHealth);
        $beastStomp->handleAttack($character, $attackData);

        $this->mergeMessages($beastStomp->getMessages());

        $this->characterHealth = $beastStomp->getCharacterHealth();
        $this->monsterHealth = $beastStomp->getMonsterHealth();

        $beastStomp->clearMessages();
    }

    /**
     * Buccaneer's Dual Gun Barrage special attack.
     *
     * @return void
     */
    public function buccaneersDualGunBarrage(Character $character, array $attackData)
    {
        $buccaneersDualGunBarrage = $this->specialAttackRegistry->get(SpecialAttackHandlerType::BUCCANEERS_DUAL_GUN_BARRAGE);

        $buccaneersDualGunBarrage->setIsRaidBoss($this->isRaidBoss);
        $buccaneersDualGunBarrage->setCharacterHealth($this->characterHealth);
        $buccaneersDualGunBarrage->setMonsterHealth($this->monsterHealth);
        $buccaneersDualGunBarrage->handleAttack($character, $attackData);

        $this->mergeMessages($buccaneersDualGunBarrage->getMessages());

        $this->characterHealth = $buccaneersDualGunBarrage->getCharacterHealth();
        $this->monsterHealth = $buccaneersDualGunBarrage->getMonsterHealth();

        $buccaneersDualGunBarrage->clearMessages();
    }
}
