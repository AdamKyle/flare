<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Affixes;
use App\Flare\ServerFight\Fight\CanHit;
use App\Flare\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;

class CastType extends BattleBase
{

    private Entrance $entrance;

    private CanHit $canHit;

    private SpecialAttacks $specialAttacks;

    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit, SpecialAttacks $specialAttacks)
    {
        parent::__construct($characterCacheData);

        $this->entrance           = $entrance;
        $this->canHit             = $canHit;
        $this->specialAttacks     = $specialAttacks;
    }

    public function setCharacterAttackData(Character $character, bool $isVoided): CastType
    {

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $isVoided ? 'voided_cast' : 'cast');
        $this->isVoided = $isVoided;

        return $this;
    }

    public function resetMessages() {
        $this->clearMessages();
        $this->entrance->clearMessages();
    }

    public function pvpCastAttack(Character $attacker, Character $defender) {
        $this->entrance->attackerEntrancesDefender($attacker, $this->attackData, $this->isVoided);

        $this->mergeAttackerMessages($this->entrance->getAttackerMessages());
        $this->mergeDefenderMessages($this->entrance->getDefenderMessages());

        $spellDamage = $this->attackData['spell_damage'];

        if ($this->entrance->isEnemyEntranced()) {
            $this->pvpSpellDamage($attacker, $defender, $spellDamage);

            return $this;
        }

        if ($this->canHit->canPlayerCastSpellOnPlayer($attacker, $defender, $this->isVoided)) {
            if ($this->characterCacheData->getCachedCharacterData($defender, 'ac') > $spellDamage) {
                $this->addAttackerMessage('Your spell was blocked!', 'enemy-action');
            } else {
                $this->pvpSpellDamage($attacker, $defender, $spellDamage);
            }
        } else {
            $this->addAttackerMessage('Your spell fizzled and failed!', 'enemy-action');

            $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);
        }

        return $this;
    }

    public function castAttack(Character $character, ServerMonster $monster) {
        $this->entrance->playerEntrance($character, $monster, $this->attackData);

        $this->mergeMessages($this->entrance->getMessages());

        $spellDamage = $this->attackData['spell_damage'];

        if ($this->entrance->isEnemyEntranced()) {
            $this->doSpellDamage($character, $monster, $spellDamage, true);

            return $this;
        }

        $this->mergeMessages($this->entrance->getMessages());

        if ($this->canHit->canPlayerAutoHit($character)) {
            $this->addMessage('You dance along in the shadows, the enemy doesn\'t see you. Strike now!', 'regular');

            $this->doSpellDamage($character, $monster, $spellDamage, true);

            return $this;
        }

        if ($this->canHit->canPlayerCastSpell($character, $monster, $this->isVoided)) {
            if ($monster->getMonsterStat('ac') > $spellDamage) {
                $this->addMessage('Your spell was blocked!', 'enemy-action');
            } else {
                $this->doSpellDamage($character, $monster, $spellDamage);
            }
        } else {
            $this->addMessage('Your spell fizzled and failed!', 'enemy-action');

            $this->secondaryAttack($character, $monster);
        }

        return $this;
    }

    public function doPvpSpellDamage(Character $attacker, Character $defender, int $spellDamage) {
        if ($spellDamage > 0) {
            $this->pvpSpellDamage($attacker, $defender, $spellDamage);
        }

        $this->heal($attacker, true);

        $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);
    }

    public function doSpellDamage(Character $character, ServerMonster $monster, int $spellDamage, bool $entranced = false) {
        if ($spellDamage > 0) {
            $this->spellDamage($character, $monster, $spellDamage, $entranced);
        }

        $this->heal($character);

        $this->secondaryAttack($character, $monster);
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

        if (!$entranced) {
            if ($monsterSpellEvasion > 1) {
                $this->addMessage('The enemy evades your magic!', 'enemy-action');

                return;
            }

            $evasion = 100 - (100 - 100 * $monsterSpellEvasion);

            if (rand(1, 100) > $evasion) {
                $this->addMessage('The enemy evades your magic!', 'enemy-action');

                return;
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

    public function heal(Character $character, bool $isPvp = false) {
        $healFor = $this->attackData['heal_for'];

        if ($healFor > 0) {
            $criticality = $this->characterCacheData->getCachedCharacterData($character, 'skills')['criticality'];

            if (rand(1, 100) > (100 - 100 * $criticality)) {
                if ($isPvp) {
                    $this->addAttackerMessage('The heavens open and your wounds start to heal over (Critical heal!)', 'player-action');
                } else {
                    $this->addMessage('The heavens open and your wounds start to heal over (Critical heal!)', 'player-action');
                }

                $healFor *= 2;
            }

            $this->characterHealth += $healFor;

            if ($this->characterHealth > $this->characterCacheData->getCachedCharacterData($character, 'health')) {
                $this->characterHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');
            }

            if ($isPvp) {
                $this->addAttackerMessage('Your healing spell(s) heals you for: ' . number_format($healFor), 'player-action');
            } else {
                $this->addMessage('Your healing spell(s) heals you for: ' . number_format($healFor), 'player-action');
            }


            $this->specialAttacks->setCharacterHealth($this->characterHealth)
                                 ->setMonsterHealth($this->monsterHealth)
                                 ->doCastHealSpecials($character, $this->attackData, $isPvp);

            $this->characterHealth = $this->specialAttacks->getCharacterHealth();

            if ($isPvp) {
                $this->mergeAttackerMessages($this->specialAttacks->getAttackerMessages());
                $this->mergeDefenderMessages($this->specialAttacks->getDefenderMessages());

                $this->characterHealth = $this->specialAttacks->getCharacterHealth();
                $this->monsterHealth   = $this->specialAttacks->getMonsterHealth();

                $this->specialAttacks->clearMessages();
            } else {
                $this->mergeMessages($this->specialAttacks->getMessages());
            }


            $this->specialAttacks->clearMessages();
        }
    }
}
