<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\CanHit;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use Cache;

class CastType extends BattleBase
{
    private Entrance $entrance;

    private CanHit $canHit;

    private SpecialAttacks $specialAttacks;

    private bool $allowEntrancing = false;

    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit, SpecialAttacks $specialAttacks)
    {
        parent::__construct($characterCacheData);

        $this->entrance = $entrance;
        $this->canHit = $canHit;
        $this->specialAttacks = $specialAttacks;
    }

    public function setCharacterAttackData(Character $character, bool $isVoided, string $type): CastType
    {

        $attackType = $type;

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $attackType);
        $this->isVoided = $isVoided;

        return $this;
    }

    public function setCharacterCastAndAttack(Character $character, bool $isVoided): CastType
    {

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $isVoided ? 'voided_cast_and_attack' : 'cast_and_attack');
        $this->isVoided = $isVoided;

        return $this;
    }

    public function setCharacterAttackAndCast(Character $character, bool $isVoided): CastType
    {

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $isVoided ? 'voided_attack_and_cast' : 'attack_and_cast');
        $this->isVoided = $isVoided;

        return $this;
    }

    public function setAllowEntrancing(bool $allow): CastType
    {

        $this->allowEntrancing = $allow;

        return $this;
    }

    public function resetMessages()
    {
        $this->clearMessages();
        $this->entrance->clearMessages();
    }

    public function castAttack(Character $character, ServerMonster $monster)
    {

        $spellDamage = $this->attackData['spell_damage'];
        $healFor = $this->attackData['heal_for'];

        if ($spellDamage <= 0) {

            $this->heal($character);

            $this->doSecondaryAttacks($character, $monster);

            return $this;
        }

        if (! $this->isEnemyEntranced && $this->allowEntrancing) {
            $this->doEnemyEntrance($character, $monster, $this->entrance);
        }

        if ($this->isEnemyEntranced) {
            $this->doSpellDamage($character, $monster, $spellDamage, true);

            if ($healFor > 0) {
                $this->heal($character);
            }

            return $this;
        }

        if ($this->canHit->canPlayerAutoHit($character)) {
            $this->addMessage('You dance along in the shadows, the enemy doesn\'t see you. Strike now!', 'regular');

            $this->doSpellDamage($character, $monster, $spellDamage, true);

            if ($healFor > 0) {
                $this->heal($character);
            }

            return $this;
        }

        if ($this->canHit->canPlayerCastSpell($character, $monster, $this->isVoided)) {
            if ($monster->getMonsterStat('ac') > $spellDamage) {
                $this->addMessage('Your damage spell was blocked!', 'enemy-action');

                if ($healFor > 0) {
                    $this->heal($character);
                }

                $this->doSecondaryAttacks($character, $monster);
            } else {
                $this->doSpellDamage($character, $monster, $spellDamage);
            }
        } else {
            $this->addMessage('Your damage spell(s) fizzled and failed!', 'enemy-action');

            if ($healFor > 0) {
                $this->heal($character);
            }

            $this->doSecondaryAttacks($character, $monster);
        }

        return $this;
    }

    public function doSpellDamage(Character $character, ServerMonster $monster, int $spellDamage, bool $entranced = false)
    {
        $this->spellDamage($character, $monster, $spellDamage, $entranced);

        if (! $entranced) {
            $this->doMonsterCounter($character, $monster);
        }

        if ($this->characterHealth <= 0) {
            $this->abortCharacterIsDead = true;

            return;
        }

        $this->doSecondaryAttacks($character, $monster);
    }

    public function spellDamage(Character $character, ServerMonster $monster, int $spellDamage, bool $entranced = false)
    {

        $monsterSpellEvasion = $monster->getMonsterStat('spell_evasion');

        $characterSpellEvasionReduction = $character->getInformation()->buildResistanceReductionChance();

        $monsterSpellEvasion -= $characterSpellEvasionReduction;

        if (! $entranced) {

            if ($monsterSpellEvasion > 0 && $characterSpellEvasionReduction < 1) {

                if ($monsterSpellEvasion > 1) {
                    $this->addMessage('The enemy evades your magic!', 'enemy-action');

                    return;
                }

                $evasion = 100 - (100 - 100 * $monsterSpellEvasion);

                if (rand(1, 100) > $evasion) {
                    $this->addMessage('The enemy evades your magic!', 'enemy-action');

                    return;
                }
            } else {
                $this->addMessage('The enemy fails to evade your magics', 'player-action');
            }
        }

        $criticality = $this->characterCacheData->getCachedCharacterData($character, 'skills')['criticality'];

        if (rand(1, 100) > (100 - 100 * $criticality)) {
            $this->addMessage('Your magic radiates across the plane. Even The Creator is terrified! (Critical strike!)', 'player-action');

            $spellDamage *= 2;
        }

        $totalDamage = $spellDamage - $spellDamage * $this->attackData['damage_deduction'];

        $this->monsterHealth -= $totalDamage;

        $this->addMessage('Your damage spell(s) hits ' . $monster->getName() . ' for: ' . number_format($totalDamage), 'player-action');

        $this->specialAttacks->setCharacterHealth($this->characterHealth)
            ->setMonsterHealth($this->monsterHealth)
            ->doCastDamageSpecials($character, $this->attackData);

        $this->characterHealth = $this->specialAttacks->getCharacterHealth();
        $this->monsterHealth = $this->specialAttacks->getMonsterHealth();

        $this->mergeMessages($this->specialAttacks->getMessages());

        $this->specialAttacks->clearMessages();
    }

    public function heal(Character $character)
    {
        $cachedHealFor = $this->getCachedHealingAmount($character);
        $maxHealth = floor($this->characterCacheData->getCachedCharacterData($character, 'health'));

        if ($cachedHealFor > 0) {
            if ($this->characterHealth < $maxHealth) {
                $this->addMessage('You reserved healing bursts forward and you feel life flowing through your veins.', 'player-action');

                $leftOver = $this->doCacheHealing($character, $maxHealth, $cachedHealFor);

                if ($leftOver <= 0) {
                    $this->heal($character);
                }

                return;
            } else {
                $this->dealChrDamage($character);
            }
        }

        $healFor = $this->attackData['heal_for'];

        if ($healFor > 0) {
            $healFor = $this->getPotentialCriticalHealAmount($character, $healFor);

            $healFor += $this->doubleCastHealingAmount($character);

            if ($this->characterHealth < $maxHealth) {
                $needToHealAmount = $maxHealth - $this->characterHealth;

                $healFor = $this->healForAllAmount($needToHealAmount, $healFor, $maxHealth);
                $healFor = $this->partialHeal($needToHealAmount, $healFor, $maxHealth);
            } else {
                $this->dealChrDamage($character);
            }

            if ($healFor > 0) {
                Cache::put('character-' . $character->id . '-healing-amount', $healFor);

                $this->addMessage('Your healing spell(s) heals you for: ' . number_format($healFor), 'player-action');
            }

            $this->specialAttacks->clearMessages();
        }
    }

    public function healDuringFight(Character $character)
    {
        $cachedHealFor = $this->getCachedHealingAmount($character);
        $maxHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');

        if ($cachedHealFor > 0) {
            if ($this->characterHealth < $maxHealth) {

                $needToHealAmount = $maxHealth - $this->characterHealth;

                $healFor = $this->healForAllAmount($needToHealAmount, $cachedHealFor, $maxHealth);
                $healFor = $this->partialHeal($needToHealAmount, $healFor, $maxHealth);

                Cache::put('character-' . $character->id . '-healing-amount', min($healFor, 0));

                $this->addMessage('You reserved healing bursts forward and you feel life flowing through your veins.', 'player-action');
            }
        }
    }

    private function getCachedHealingAmount(Character $character): int
    {
        return (int) Cache::get('character-' . $character->id . '-healing-amount', 0);
    }

    private function doCacheHealing(Character $character, int $maxHealth, int $cachedHealFor): int
    {
        $needToHealAmount = $maxHealth - $this->characterHealth;

        $healFor = $this->healForAllAmount($needToHealAmount, $cachedHealFor, $maxHealth);
        $healFor = $this->partialHeal($needToHealAmount, $healFor, $maxHealth);

        Cache::put('character-' . $character->id . '-healing-amount', $healFor);

        return $healFor;
    }

    private function dealChrDamage(Character $character): void
    {
        $isValidHealer = ($character->classType()->isProphet() || $character->classType()->isCleric());

        $chrDamage = $this->characterCacheData->getCachedCharacterData($character, 'chr_modded') * ($isValidHealer ? 0.25 : 0.05);

        $this->monsterHealth -= $chrDamage;

        $this->addMessage('Your prayers for health rage at the enemy as you lash out in a fevered holy pitch for: ' . number_format($chrDamage) . '!', 'player-action');
    }

    private function getPotentialCriticalHealAmount(Character $character, int $healFor): int
    {
        $criticality = $this->characterCacheData->getCachedCharacterData($character, 'skills')['criticality'];

        if (rand(1, 100) > (100 - 100 * $criticality)) {
            $this->addMessage('The heavens open and your wounds start to heal over (Critical heal!)', 'player-action');

            $healFor *= 2;
        }

        $this->addMessage('Your healing spell(s) erupt around you for: ' . number_format($healFor), 'player-action');

        return $healFor;
    }

    private function doubleCastHealingAmount(Character $character): int
    {
        $healForAmount = $this->specialAttacks->setCharacterHealth($this->characterHealth)
            ->setMonsterHealth($this->monsterHealth)
            ->doCastHealSpecials($character, $this->attackData)
            ->getHealFor();

        $this->mergeMessages($this->specialAttacks->getMessages());

        return $healForAmount;
    }

    private function healForAllAmount(int $needToHealAmount, int $healFor, int $maxHealth): int
    {
        if ($needToHealAmount >= $healFor) {

            $this->characterHealth += min($healFor, $maxHealth);

            $this->addMessage('Your healing spell(s) heals you completely for: ' . number_format($healFor), 'player-action');

            return 0;
        }

        return $healFor;
    }

    private function partialHeal(int $needToHealAmount, int $healFor, int $maxHealth): int
    {
        if ($needToHealAmount > 0 && $healFor > 0) {
            $amountToHeal = min($needToHealAmount, $healFor, $maxHealth - $this->characterHealth);

            $this->characterHealth += $amountToHeal;

            $this->addMessage('Your healing spell(s) partially heals you for: ' . number_format($amountToHeal), 'player-action');

            $healFor -= $amountToHeal;
        }

        return $healFor;
    }

    protected function doSecondaryAttacks($character, $monster)
    {
        if ($this->allowSecondaryAttacks && ! $this->abortCharacterIsDead) {
            $this->secondaryAttack($character, $monster);
        }
    }
}
