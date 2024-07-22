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

    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit, SpecialAttacks $specialAttacks) {
        parent::__construct($characterCacheData);

        $this->entrance           = $entrance;
        $this->canHit             = $canHit;
        $this->specialAttacks     = $specialAttacks;
    }

    public function setCharacterAttackData(Character $character, bool $isVoided, string $type): CastType{

        $attackType = $isVoided ? 'voided_' . $type : $type;

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $attackType);
        $this->isVoided   = $isVoided;

        return $this;
    }


    public function setCharacterCastAndAttack(Character $character, bool $isVoided): CastType {

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $isVoided ? 'voided_cast_and_attack' : 'cast_and_attack');
        $this->isVoided = $isVoided;

        return $this;
    }

    public function setAllowEntrancing(bool $allow): CastType {

        $this->allowEntrancing = $allow;

        return $this;
    }

    public function resetMessages() {
        $this->clearMessages();
        $this->entrance->clearMessages();
    }

    public function pvpCastAttack(Character $attacker, Character $defender) {

        $spellDamage = $this->attackData['spell_damage'];

        if ($spellDamage > 0) {
            $this->heal($attacker, $defender, true);

            if ($this->allowSecondaryAttacks) {
                $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);
            }

            return;
        }

        if (!$this->isEnemyEntranced) {
            $this->doPvpEntrance($attacker, $this->entrance);

            if ($this->isEnemyEntranced) {
                $this->pvpSpellDamage($attacker, $defender, $spellDamage);

                if ($this->allowSecondaryAttacks) {
                    $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);
                }

                return $this;
            }
        } else if ($this->isEnemyEntranced) {
            $this->pvpSpellDamage($attacker, $defender, $spellDamage);

            if ($this->allowSecondaryAttacks) {
                $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);
            }

            return $this;
        }

        if ($this->canHit->canPlayerCastSpellOnPlayer($attacker, $defender, $this->isVoided)) {
            if ($this->characterCacheData->getCachedCharacterData($defender, 'ac') > $spellDamage) {
                $this->addAttackerMessage('Your spell was blocked!', 'enemy-action');
            } else {
                $this->pvpSpellDamage($attacker, $defender, $spellDamage);

                if ($this->allowSecondaryAttacks) {
                    $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);
                }
            }
        } else {
            $this->addAttackerMessage('Your spell fizzled and failed!', 'enemy-action');

            if ($this->allowSecondaryAttacks) {
                $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);
            }
        }

        return $this;
    }

    public function castAttack(Character $character, ServerMonster $monster) {

        $spellDamage = $this->attackData['spell_damage'];

        if ($spellDamage <= 0) {

            $this->heal($character);

            $this->doSecondaryAttacks($character, $monster);

            return $this;
        }

        if (!$this->isEnemyEntranced && $this->allowEntrancing) {
            $this->doEnemyEntrance($character, $monster, $this->entrance);
        }

        if ($this->isEnemyEntranced) {
            $this->doSpellDamage($character, $monster, $spellDamage, true);

            return $this;
        }

        if ($this->canHit->canPlayerAutoHit($character)) {
            $this->addMessage('You dance along in the shadows, the enemy doesn\'t see you. Strike now!', 'regular');

            $this->doSpellDamage($character, $monster, $spellDamage, true);

            return $this;
        }

        if ($this->canHit->canPlayerCastSpell($character, $monster, $this->isVoided)) {
            if ($monster->getMonsterStat('ac') > $spellDamage) {
                $this->addMessage('Your spell was blocked!', 'enemy-action');

                $this->doSecondaryAttacks($character, $monster);
            } else {
                $this->doSpellDamage($character, $monster, $spellDamage);
            }
        } else {
            $this->addMessage('Your spell fizzled and failed!', 'enemy-action');

            $this->doSecondaryAttacks($character, $monster);
        }

        return $this;
    }

    public function doPvpSpellDamage(Character $attacker, Character $defender, int $spellDamage) {
        $this->pvpSpellDamage($attacker, $defender, $spellDamage);

        if ($this->allowSecondaryAttacks) {
            $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);
        }
    }

    public function doSpellDamage(Character $character, ServerMonster $monster, int $spellDamage, bool $entranced = false) {
        $this->spellDamage($character, $monster, $spellDamage, $entranced);

        if (!$entranced) {
            $this->doMonsterCounter($character, $monster);
        }

        if ($this->characterHealth <= 0) {
            $this->abortCharacterIsDead = true;

            return;
        }

        $this->doSecondaryAttacks($character, $monster);
    }

    public function pvpSpellDamage(Character $attacker, Character $defender, int $spellDamage, bool $outSideEntrance = false) {
        $defenderSpellEvasion = $this->characterCacheData->getCachedCharacterData($defender, 'spell_evasion');

        if (!$outSideEntrance) {
            if (!$this->isEnemyEntranced) {
                if ($defenderSpellEvasion > 1) {
                    $this->addAttackerMessage('The enemy evades your magic!', 'enemy-action');
                    $this->addDefenderMessage('Your rings glow and you manage to evade the enemies spells.', 'player-action');

                    return;
                }

                $evasion = 100 - (100 - 100 * $defenderSpellEvasion);

                if (rand(1, 100) > $evasion) {
                    $this->addAttackerMessage('The enemy evades your magic!', 'enemy-action');
                    $this->addDefenderMessage('Your rings glow and you manage to evade the enemies spells.', 'player-action');
                }
            }
        }

        $criticality = $this->characterCacheData->getCachedCharacterData($attacker, 'skills')['criticality'];

        if (rand(1, 100) > (100 - 100 * $criticality)) {
            $this->addAttackerMessage('Your magic radiates across the plane. Even The Creator is terrified! (Critical strike!)', 'player-action');

            $spellDamage *= 2;
        }

        $totalDamage = $spellDamage - $spellDamage * $this->attackData['damage_deduction'];

        $this->monsterHealth -= $totalDamage;

        $this->addAttackerMessage('Your damage spell(s) hits ' . $defender->name . ' for: ' . number_format($totalDamage), 'player-action');
        $this->addDefenderMessage($attacker->name . ' begins to cast their magics, the crackle in the air is electrifying. Their magics fly towards you for: ' . number_format($totalDamage), 'enemy-action');

        $this->pvpCounter($attacker, $defender);

        if ($this->abortCharacterIsDead) {
            return;
        }

        $this->specialAttacks->setCharacterHealth($this->characterHealth)
                             ->setMonsterHealth($this->monsterHealth)
                             ->doCastDamageSpecials($attacker, $this->attackData, true);

        $this->mergeAttackerMessages($this->specialAttacks->getAttackerMessages());
        $this->mergeDefenderMessages($this->specialAttacks->getDefenderMessages());

        $this->characterHealth = $this->specialAttacks->getCharacterHealth();
        $this->monsterHealth   = $this->specialAttacks->getMonsterHealth();

        $this->specialAttacks->clearMessages();
    }

    public function spellDamage(Character $character, ServerMonster $monster, int $spellDamage, bool $entranced = false) {

        $monsterSpellEvasion = $monster->getMonsterStat('spell_evasion');

        $characterSpellEvasionReduction = $character->getInformation()->buildResistanceReductionChance();

        $monsterSpellEvasion -= $characterSpellEvasionReduction;

        if (!$entranced) {

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
        $this->monsterHealth   = $this->specialAttacks->getMonsterHealth();

        $this->mergeMessages($this->specialAttacks->getMessages());

        $this->specialAttacks->clearMessages();
    }

    public function heal(Character $character, Character $defender = null, bool $isPvp = false) {
        $cachedHealFor = $this->getCachedHealingAmount($character);
        $maxHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');

        if ($cachedHealFor > 0) {
            if ($this->characterHealth < $maxHealth) {

                if ($isPvp) {
                    $this->addAttackerMessage('You reserved healing bursts forward and you feel life flowing through your veins.', 'player-action');
                } else {
                    $this->addMessage('You reserved healing bursts forward and you feel life flowing through your veins.', 'player-action');
                }

                $leftOver = $this->doCacheHealing($character, $maxHealth, $cachedHealFor, $isPvp);

                if ($leftOver <= 0) {
                    $this->heal($character, $defender, $isPvp);
                }

                return;
            } else {
                $this->dealChrDamage($character, $isPvp);
            }
        }

        $healFor = $this->attackData['heal_for'];

        $healFor = $this->getHealingReductionWhenPvp($healFor, $isPvp, $defender);

        if ($healFor > 0) {
            $healFor = $this->getPotentialCriticalHealAmount($character, $healFor, $isPvp);


            $healFor += $this->doubleCastHealingAmount($character, $isPvp);

            if ($this->characterHealth < $maxHealth) {
                $needToHealAmount = $maxHealth - $this->characterHealth;

                $healFor = $this->healForAllAmount($needToHealAmount, $healFor, $maxHealth, $isPvp);
                $healFor = $this->partialHeal($needToHealAmount, $healFor, $maxHealth, $isPvp);
            } else {
                $this->dealChrDamage($character, $isPvp);
            }

            if ($healFor > 0) {
                Cache::put('character-' . $character->id . '-healing-amount', $healFor);

                if ($isPvp) {
                    $this->addAttackerMessage('You feel the healing energy wash over you and recede deep inside your body and soul for when you need it. Amount Stored:' . number_format($healFor), 'player-action');
                } else {
                    $this->addMessage('Your healing spell(s) heals you for: ' . number_format($healFor), 'player-action');
                }
            }

            $this->specialAttacks->clearMessages();
        }
    }

    public function healDuringFight(Character $character, bool $isPvp = false) {
        $cachedHealFor = $this->getCachedHealingAmount($character);
        $maxHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');

        if ($cachedHealFor > 0) {
            if ($this->characterHealth < $maxHealth) {

                if ($isPvp) {
                    $this->addAttackerMessage('You reserved healing bursts forward and you feel life flowing through your veins.', 'player-action');
                } else {
                    $this->addMessage('You reserved healing bursts forward and you feel life flowing through your veins.', 'player-action');
                }
            }
        }
    }

    private function getCachedHealingAmount(Character $character): int {
        return (int) Cache::get('character-' . $character->id . '-healing-amount', 0);
    }

    private function doCacheHealing(Character $character, int $maxHealth, int $cachedHealFor, bool $isPvp): int {
        $needToHealAmount = $maxHealth - $this->characterHealth;

        $healFor = $this->healForAllAmount($needToHealAmount, $cachedHealFor, $maxHealth, $isPvp);
        $healFor = $this->partialHeal($needToHealAmount, $healFor, $maxHealth, $isPvp);

        Cache::put('character-' . $character->id . '-healing-amount', $healFor);

        return $healFor;
    }

    private function dealChrDamage(Character $character, bool $isPvp): void {
        $isValidHealer = ($character->classType()->isProphet() || $character->classType()->isCleric());
        $chrDamage = $this->attackData['chr_modded'] * ($isValidHealer ? 0.25 : 0.05);

        $this->monsterHealth -= $chrDamage;

        if ($isPvp) {
            $this->addAttackerMessage('Your prayers for health rage at the enemy as you lash out in a fevered holy pitch for: ' . number_format($chrDamage) . '!', 'player-action');
            $this->addDefenderMessage('The enemy before you starts to pray for health, alas their holy rage flies towards you for: ' . number_format($chrDamage) . '!', 'enemy-action');
        } else {
            $this->addMessage('Your prayers for health rage at the enemy as you lash out in a fevered holy pitch for: ' . number_format($chrDamage) . '!', 'player-action');
        }

        return;
    }

    private function getHealingReductionWhenPvp(int $healFor, bool $isPvp, Character $defender = null): int {
        if (!is_null($defender) && $isPvp) {
            $reduction = $this->characterCacheData->getCachedCharacterData($defender, 'healing_reduction');

            if ($reduction > 0.0) {
                $healFor -= $healFor * $reduction;

                $this->addDefenderMessage('You manage to reduce the enemies ability to heal fully!', 'player-action');
                $this->addAttackerMessage('Your healing prayers and spells seem weaker in the face of your enemy!', 'enemy-action');
            }
        }

        return $healFor;
    }

    private function getPotentialCriticalHealAmount(Character $character, int $healFor, bool $isPvp): int {
        $criticality = $this->characterCacheData->getCachedCharacterData($character, 'skills')['criticality'];

        if (rand(1, 100) > (100 - 100 * $criticality)) {
            if ($isPvp) {
                $this->addAttackerMessage('The heavens open and your wounds start to heal over (Critical heal!)', 'player-action');
            } else {
                $this->addMessage('The heavens open and your wounds start to heal over (Critical heal!)', 'player-action');
            }

            $healFor *= 2;
        }

        if ($isPvp) {
            $this->addAttackerMessage('Your healing spell(s) erupt around you for: ' . number_format($healFor), 'player-action');
        } else {
            $this->addMessage('Your healing spell(s) heals erupt around you for: ' . number_format($healFor), 'player-action');
        }

        return $healFor;
    }

    private function doubleCastHealingAmount(Character $character, bool $isPvp): int {
        $healForAmount = $this->specialAttacks->setCharacterHealth($this->characterHealth)
            ->setMonsterHealth($this->monsterHealth)
            ->doCastHealSpecials($character, $this->attackData, $isPvp)
            ->getHealFor();

        if ($isPvp) {
            $this->mergeAttackerMessages($this->specialAttacks->getAttackerMessages());
            $this->mergeDefenderMessages($this->specialAttacks->getDefenderMessages());
        } else {
            $this->mergeMessages($this->specialAttacks->getMessages());
        }

        return $healForAmount;
    }

    private function healForAllAmount(int $needToHealAmount, int$healFor, int $maxHealth, bool $isPvp): int {
        if ($needToHealAmount >= $healFor) {
            $this->characterHealth += min($healFor, $maxHealth);


            if ($isPvp) {
                $this->addAttackerMessage('Your healing spell(s) heals you for: ' . number_format($healFor), 'player-action');
            } else {
                $this->addMessage('Your healing spell(s) heals you for: ' . number_format($healFor), 'player-action');
            }

            return 0;
        }

        return $healFor;
    }

    private function partialHeal(int $needToHealAmount, int$healFor, int $maxHealth, bool $isPvp): int {
        if ($needToHealAmount < $healFor && $healFor > 0) {
            $amountToUse = $healFor - $needToHealAmount;

            $this->characterHealth += min($healFor, $maxHealth);


            if ($isPvp) {
                $this->addAttackerMessage('Your healing spell(s) heals you for: ' . number_format($amountToUse), 'player-action');
            } else {
                $this->addMessage('Your healing spell(s) heals you for: ' . number_format($amountToUse), 'player-action');
            }

            $healFor = min($healFor - $amountToUse, 0);
        }

        return $healFor;
    }

    protected function doSecondaryAttacks($character, $monster) {
        if ($this->allowSecondaryAttacks && !$this->abortCharacterIsDead) {
            $this->secondaryAttack($character, $monster);
        }
    }

    protected function entrancePlayer(Character $attacker, Character $defender, int $spellDamage) {
        $this->entrance->attackerEntrancesDefender($attacker, $this->attackData, $this->isVoided);

        $this->mergeAttackerMessages($this->entrance->getAttackerMessages());
        $this->mergeDefenderMessages($this->entrance->getDefenderMessages());

        return $this->doEntrancePvpDamage($attacker, $defender, $spellDamage);
    }

    protected function doEntrancePvpDamage($attacker, $defender, $spellDamage) {
        if ($this->entrance->isEnemyEntranced()) {
            $this->isEnemyEntranced = true;

            $this->pvpSpellDamage($attacker, $defender, $spellDamage);

            if ($this->allowSecondaryAttacks) {
                $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);
            }

            return true;
        }

        return false;
    }
}
